<?php
/**
 * CSP Solver dengan Algoritma Backtracking
 * SMART EDUCATION PAREPARE
 * 
 * Constraint Satisfaction Problem:
 * - Variables: Kelas bimbingan belajar (C_1, C_2, ..., C_n)
 * - Domain: Kombinasi (Hari, Jam Belajar, Ruangan)
 * - Constraints:
 *   1. Tutor tidak mengajar dua kelas pada waktu bersamaan
 *   2. Ruangan tidak digunakan dua kelas secara bersamaan
 *   3. Jadwal siswa tidak boleh bertabrakan (siswa di 2 kelas berbeda)
 *   4. Kapasitas ruangan >= jumlah siswa terdaftar di kelas
 */

class CSPSolver {
    private $pdo;
    private $days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    private $timeSlots = [
        ['slot' => 1, 'mulai' => '08:30:00', 'selesai' => '10:00:00', 'label' => '08:30 - 10:00 WITA'],
        ['slot' => 2, 'mulai' => '10:15:00', 'selesai' => '11:45:00', 'label' => '10:15 - 11:45 WITA'],
        ['slot' => 3, 'mulai' => '13:30:00', 'selesai' => '15:00:00', 'label' => '13:30 - 15:00 WITA'],
        ['slot' => 4, 'mulai' => '15:30:00', 'selesai' => '17:00:00', 'label' => '15:30 - 17:00 WITA'],
        ['slot' => 5, 'mulai' => '19:00:00', 'selesai' => '20:30:00', 'label' => '19:00 - 20:30 WITA'],
    ];
    private $rooms = [];
    private $classes = [];
    private $conflictLog = [];
    private $stepsExplored = 0;
    private $backtrackCount = 0;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->loadData();
    }

    private function loadData() {
        // Load available rooms
        $stmt_r = $this->pdo->query("SELECT * FROM ruangan WHERE status_ruangan = 'Tersedia' ORDER BY kapasitas DESC");
        $this->rooms = $stmt_r->fetchAll();

        // Load active classes
        $stmt_c = $this->pdo->query("
            SELECT k.*, p.nama_paket, t.nama_tutor
            FROM kelas k
            JOIN paket_belajar p ON k.id_paket = p.id_paket
            JOIN tutor t ON k.id_tutor = t.id_tutor
            WHERE k.status_kelas = 'Aktif'
            ORDER BY k.id_kelas ASC
        ");
        $raw_classes = $stmt_c->fetchAll();

        // For each class, fetch enrolled students
        $this->classes = [];
        foreach ($raw_classes as $cls) {
            $stmt_s = $this->pdo->prepare("SELECT id_siswa FROM anggota_kelas WHERE id_kelas = :id_kelas");
            $stmt_s->execute([':id_kelas' => $cls['id_kelas']]);
            $student_ids = $stmt_s->fetchAll(PDO::FETCH_COLUMN);

            $cls['siswa_ids'] = $student_ids;
            $cls['total_siswa'] = count($student_ids);
            $this->classes[] = $cls;
        }

        // Sort variables using Most Constrained / Degree Heuristic (classes with most students first)
        usort($this->classes, function($a, $b) {
            return $b['total_siswa'] <=> $a['total_siswa'];
        });
    }

    public function getClasses() {
        return $this->classes;
    }

    public function getRooms() {
        return $this->rooms;
    }

    public function getDays() {
        return $this->days;
    }

    public function getTimeSlots() {
        return $this->timeSlots;
    }

    /**
     * Check if assigning $domain to $class is consistent with existing $assignment
     */
    private function isConsistent($class, $domain, $assignment, &$conflictReason = null) {
        $c_tutor = $class['id_tutor'];
        $c_students = $class['siswa_ids'];
        $c_total_students = $class['total_siswa'];

        $d_day = $domain['hari'];
        $d_slot = $domain['slot_data']['slot'];
        $d_room = $domain['ruangan'];

        // Kendala 4: Kapasitas Ruangan harus memadai
        if ($d_room['kapasitas'] < $c_total_students) {
            $conflictReason = "Kapasitas ruangan {$d_room['nama_ruangan']} ({$d_room['kapasitas']}) tidak mencukupi untuk {$c_total_students} siswa.";
            return false;
        }

        // Check against each already assigned class
        foreach ($assignment as $assigned_class_id => $assigned_domain) {
            $assigned_class = null;
            foreach ($this->classes as $c) {
                if ($c['id_kelas'] == $assigned_class_id) {
                    $assigned_class = $c;
                    break;
                }
            }
            if (!$assigned_class) continue;

            $a_day = $assigned_domain['hari'];
            $a_slot = $assigned_domain['slot_data']['slot'];
            $a_room = $assigned_domain['ruangan'];
            $a_tutor = $assigned_class['id_tutor'];
            $a_students = $assigned_class['siswa_ids'];

            // If same Day and same TimeSlot -> Check for collisions!
            if ($d_day === $a_day && $d_slot === $a_slot) {
                // Kendala 1: Tutor Collision
                if ($c_tutor == $a_tutor) {
                    $conflictReason = "Bentrok Tutor: {$class['nama_tutor']} sudah mengajar kelas '{$assigned_class['nama_kelas']}' pada {$d_day} {$domain['slot_data']['label']}.";
                    return false;
                }

                // Kendala 2: Room Collision
                if ($d_room['id_ruangan'] == $a_room['id_ruangan']) {
                    $conflictReason = "Bentrok Ruangan: {$d_room['nama_ruangan']} sudah digunakan kelas '{$assigned_class['nama_kelas']}' pada {$d_day} {$domain['slot_data']['label']}.";
                    return false;
                }

                // Kendala 3: Student Collision
                $intersect = array_intersect($c_students, $a_students);
                if (!empty($intersect)) {
                    $conflictReason = "Bentrok Siswa: Terdapat " . count($intersect) . " siswa yang terdaftar di kedua kelas ('{$class['nama_kelas']}' dan '{$assigned_class['nama_kelas']}') pada waktu bersamaan ({$d_day} {$domain['slot_data']['label']}).";
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Backtracking Search Algorithm
     */
    private function backtrack($index, $assignment) {
        $this->stepsExplored++;

        // Base Case: All classes have been scheduled successfully!
        if ($index >= count($this->classes)) {
            return $assignment;
        }

        $class = $this->classes[$index];

        // Generate Domain Values (Day x Slot x Room)
        foreach ($this->days as $day) {
            foreach ($this->timeSlots as $slot) {
                foreach ($this->rooms as $room) {
                    $domain = [
                        'hari' => $day,
                        'slot_data' => $slot,
                        'ruangan' => $room
                    ];

                    $conflictReason = null;
                    if ($this->isConsistent($class, $domain, $assignment, $conflictReason)) {
                        // Forward step
                        $assignment[$class['id_kelas']] = $domain;

                        $result = $this->backtrack($index + 1, $assignment);
                        if ($result !== false) {
                            return $result; // Solution found in sub-tree!
                        }

                        // Backtrack step
                        $this->backtrackCount++;
                        unset($assignment[$class['id_kelas']]);
                    } else {
                        // Log sample conflict for diagnostics
                        if (count($this->conflictLog) < 15 && $conflictReason) {
                            $this->conflictLog[] = [
                                'kelas' => $class['nama_kelas'],
                                'tutor' => $class['nama_tutor'],
                                'hari' => $day,
                                'slot' => $slot['label'],
                                'ruangan' => $room['nama_ruangan'],
                                'alasan' => $conflictReason
                            ];
                        }
                    }
                }
            }
        }

        return false; // No consistent assignment in this branch
    }

    /**
     * Execute solver
     */
    public function solve() {
        $this->stepsExplored = 0;
        $this->backtrackCount = 0;
        $this->conflictLog = [];

        if (empty($this->classes)) {
            return [
                'success' => false,
                'message' => 'Tidak ada kelas aktif yang memerlukan penjadwalan.',
                'solution' => [],
                'steps' => 0,
                'backtracks' => 0,
                'conflicts' => []
            ];
        }

        if (empty($this->rooms)) {
            return [
                'success' => false,
                'message' => 'Tidak ada ruangan belajar yang berstatus Tersedia di database.',
                'solution' => [],
                'steps' => 0,
                'backtracks' => 0,
                'conflicts' => []
            ];
        }

        $assignment = [];
        $solution = $this->backtrack(0, $assignment);

        if ($solution !== false) {
            // Format solution with full metadata
            $formatted = [];
            foreach ($solution as $id_kelas => $dom) {
                $cls = null;
                foreach ($this->classes as $c) {
                    if ($c['id_kelas'] == $id_kelas) {
                        $cls = $c;
                        break;
                    }
                }
                $formatted[] = [
                    'id_kelas' => $id_kelas,
                    'kode_kelas' => $cls['kode_kelas'],
                    'nama_kelas' => $cls['nama_kelas'],
                    'nama_paket' => $cls['nama_paket'],
                    'id_tutor' => $cls['id_tutor'],
                    'nama_tutor' => $cls['nama_tutor'],
                    'id_ruangan' => $dom['ruangan']['id_ruangan'],
                    'nama_ruangan' => $dom['ruangan']['nama_ruangan'],
                    'kapasitas_ruangan' => $dom['ruangan']['kapasitas'],
                    'total_siswa' => $cls['total_siswa'],
                    'hari' => $dom['hari'],
                    'jam_mulai' => $dom['slot_data']['mulai'],
                    'jam_selesai' => $dom['slot_data']['selesai'],
                    'slot_label' => $dom['slot_data']['label']
                ];
            }

            return [
                'success' => true,
                'message' => 'Solusi penjadwalan optimal tanpa konflik berhasil ditemukan!',
                'solution' => $formatted,
                'steps' => $this->stepsExplored,
                'backtracks' => $this->backtrackCount,
                'conflicts' => $this->conflictLog
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Penjadwalan belum dapat dibuat karena tidak ditemukan kombinasi yang memenuhi seluruh kendala (Constraint Satisfaction Problem).',
                'solution' => [],
                'steps' => $this->stepsExplored,
                'backtracks' => $this->backtrackCount,
                'conflicts' => $this->conflictLog
            ];
        }
    }
}

