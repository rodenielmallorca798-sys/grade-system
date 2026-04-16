<?php
// index.php
include 'db_connect.php';

// Get system ID from URL parameter (default to BSIT3B)
$system_id = trim($_GET['system'] ?? 'BSIT3B');
$system_id = preg_replace('/[^a-zA-Z0-9\s\-_]/', '', $system_id); // Sanitize

$message = "";
$students = [];

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_student'])) {
    $student_name = trim($_POST['name']);
    $quiz = floatval($_POST['quiz']);
    $laboratory = floatval($_POST['laboratory']);
    $assignment = floatval($_POST['assignment']);
    $attendance = floatval($_POST['attendance']);
    $major_exam = floatval($_POST['major_exam']);
    
    // Calculate final grade with weights
    // Quiz: 20%, Laboratory: 30%, Assignment: 10%, Attendance: 10%, Major Exam: 30%
    $final_grade = round(
        ($quiz * 0.20) + 
        ($laboratory * 0.30) + 
        ($assignment * 0.10) + 
        ($attendance * 0.10) + 
        ($major_exam * 0.30),
        2
    );

    $data = [
        'system_id' => $system_id,
        'student_name' => $student_name,
        'quiz' => $quiz,
        'laboratory' => $laboratory,
        'assignment' => $assignment,
        'attendance' => $attendance,
        'major_exam' => $major_exam,
        'final_grade' => $final_grade
    ];

    $result = $conn->insert('grades', $data);
    
    // Set message based on result
    $message = $result === true ? "success" : "error";
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->delete('grades', ['id' => $id]);
    header("Location: index.php?system=" . urlencode($system_id));
    exit();
}

// Fetch all students for this system only
$all_students = $conn->select('grades') ?? [];
$students = array_filter($all_students, function($student) use ($system_id) {
    return $student['system_id'] === $system_id;
});
// Sort by created_at ascending (oldest first, newest last)
usort($students, function($a, $b) {
    return strtotime($a['created_at'] ?? '1970-01-01') - strtotime($b['created_at'] ?? '1970-01-01');
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BSIT 3B — Grade System</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg:       #0d0f14;
            --surface:  #161920;
            --border:   #252932;
            --accent1:  #5bffa0;
            --accent2:  #ffce3b;
            --accent3:  #ff6b6b;
            --accent4:  #56c8ff;
            --accent5:  #d98aff;
            --text:     #e8ecf0;
            --muted:    #6b7280;
            --radius:   10px;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'DM Mono', monospace;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            background-image:
                radial-gradient(ellipse 80% 50% at 10% 0%, rgba(91,255,160,0.06) 0%, transparent 60%),
                radial-gradient(ellipse 60% 40% at 90% 100%, rgba(86,200,255,0.06) 0%, transparent 60%);
        }

        header {
            padding: 36px 40px 28px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: flex-end;
            gap: 20px;
        }

        .header-tag {
            font-family: 'Syne', sans-serif;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: var(--accent1);
            border: 1px solid var(--accent1);
            padding: 3px 10px;
            border-radius: 4px;
        }

        header h1 {
            font-family: 'Syne', sans-serif;
            font-size: 30px;
            font-weight: 800;
            letter-spacing: -0.5px;
            line-height: 1;
        }

        header span { color: var(--muted); font-size: 13px; margin-left: auto; }

        .container { max-width: 1150px; margin: 0 auto; padding: 36px 28px; }

        .subject-chips {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 32px;
        }

        .chip {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 500;
            border: 1px solid;
        }

        .chip-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
        .c1 { border-color: var(--accent1); color: var(--accent1); } .c1 .chip-dot { background: var(--accent1); }
        .c2 { border-color: var(--accent2); color: var(--accent2); } .c2 .chip-dot { background: var(--accent2); }
        .c3 { border-color: var(--accent3); color: var(--accent3); } .c3 .chip-dot { background: var(--accent3); }
        .c4 { border-color: var(--accent4); color: var(--accent4); } .c4 .chip-dot { background: var(--accent4); }
        .c5 { border-color: var(--accent5); color: var(--accent5); } .c5 .chip-dot { background: var(--accent5); }

        .grid {
            display: grid;
            grid-template-columns: 340px 1fr;
            gap: 24px;
            align-items: start;
        }

        @media (max-width: 820px) { .grid { grid-template-columns: 1fr; } }

        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 28px;
        }

        .card-title {
            font-family: 'Syne', sans-serif;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 22px;
        }

        .form-group { margin-bottom: 15px; }

        label {
            display: block;
            font-size: 11px;
            font-weight: 500;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 7px;
        }

        label .dot {
            display: inline-block;
            width: 8px; height: 8px;
            border-radius: 50%;
            margin-right: 6px;
            vertical-align: middle;
        }

        input[type="text"],
        input[type="number"] {
            width: 100%;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 7px;
            padding: 10px 14px;
            font-family: 'DM Mono', monospace;
            font-size: 14px;
            color: var(--text);
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        input:focus {
            border-color: var(--accent1);
            box-shadow: 0 0 0 3px rgba(91,255,160,0.1);
        }

        .divider { height: 1px; background: var(--border); margin: 18px 0; }

        .btn-submit {
            width: 100%;
            background: var(--accent1);
            color: #0d0f14;
            border: none;
            padding: 12px;
            border-radius: 8px;
            font-family: 'Syne', sans-serif;
            font-size: 13px;
            font-weight: 800;
            letter-spacing: 2px;
            text-transform: uppercase;
            cursor: pointer;
            transition: opacity 0.2s, transform 0.1s;
        }

        .btn-submit:hover  { opacity: 0.88; }
        .btn-submit:active { transform: scale(0.98); }

        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 24px;
            border: 1px solid;
        }

        .alert-success { background: rgba(91,255,160,0.08); border-color: var(--accent1); color: var(--accent1); }
        .alert-error   { background: rgba(255,107,107,0.08); border-color: var(--accent3); color: var(--accent3); }

        .table-wrapper { overflow-x: auto; }

        table { width: 100%; border-collapse: collapse; font-size: 13px; }

        thead tr { border-bottom: 1px solid var(--border); }

        th {
            padding: 10px 12px;
            text-align: center;
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: var(--muted);
        }

        th:nth-child(2), td:nth-child(2) { text-align: left; }

        td {
            padding: 13px 12px;
            text-align: center;
            border-bottom: 1px solid var(--border);
        }

        tbody tr { transition: background 0.15s; }
        tbody tr:hover { background: rgba(255,255,255,0.025); }

        .name-cell { font-family: 'Syne', sans-serif; font-weight: 600; font-size: 14px; }

        .avg-value { font-family: 'Syne', sans-serif; font-weight: 700; font-size: 16px; }

        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 50px;
            font-size: 11px;
            font-weight: 600;
            font-family: 'Syne', sans-serif;
            letter-spacing: 0.5px;
        }

        .b-excellent { background: rgba(91,255,160,0.15);  color: var(--accent1); }
        .b-good      { background: rgba(86,200,255,0.15);  color: var(--accent4); }
        .b-average   { background: rgba(255,206,59,0.15);  color: var(--accent2); }
        .b-poor      { background: rgba(255,107,107,0.15); color: var(--accent3); }

        .btn-delete {
            background: transparent;
            color: var(--muted);
            border: 1px solid var(--border);
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            cursor: pointer;
            text-decoration: none;
            font-family: 'DM Mono', monospace;
            transition: color 0.2s, border-color 0.2s;
        }

        .btn-delete:hover { color: var(--accent3); border-color: var(--accent3); }

        .no-data { text-align: center; padding: 40px; color: var(--muted); font-size: 13px; }

        footer {
            text-align: center;
            padding: 24px;
            color: var(--muted);
            font-size: 11px;
            letter-spacing: 1px;
            border-top: 1px solid var(--border);
        }
    </style>
</head>
<body>

<header>
    <div>
        <div class="header-tag">2nd Sem A.Y. 2025–2026</div>
        <h1><?= htmlspecialchars($system_id) ?> &mdash; Grading System</h1>
    </div>
</header>

<div class="container">

    <?php if ($message === "success"): ?>
        <div class="alert alert-success">✓ Student record saved successfully.</div>
    <?php elseif ($message === "error"): ?>
        <div class="alert alert-error">✗ Error saving record. Please try again.</div>
    <?php endif; ?>

    <div class="subject-chips">
        <div class="chip c1"><span class="chip-dot"></span> Quiz (20%)</div>
        <div class="chip c2"><span class="chip-dot"></span> Laboratory (30%)</div>
        <div class="chip c3"><span class="chip-dot"></span> Assignment (10%)</div>
        <div class="chip c4"><span class="chip-dot"></span> Attendance (10%)</div>
        <div class="chip c5"><span class="chip-dot"></span> Major Exam (30%)</div>
    </div>

    <div class="grid">

        <!-- FORM CARD -->
        <div class="card">
            <div class="card-title">Add Student Record</div>
            <form method="POST" action="index.php?system=<?= urlencode($system_id) ?>">

                <div class="form-group">
                    <label>Student Name</label>
                    <input type="text" name="name" placeholder="Full name" required>
                </div>

                <div class="divider"></div>

                <div class="form-group">
                    <label><span class="dot" style="background:var(--accent1)"></span>Quiz (20%)</label>
                    <input type="number" name="quiz" min="0" max="100" step="0.01" placeholder="0 – 100" required>
                </div>

                <div class="form-group">
                    <label><span class="dot" style="background:var(--accent2)"></span>Laboratory (30%)</label>
                    <input type="number" name="laboratory" min="0" max="100" step="0.01" placeholder="0 – 100" required>
                </div>

                <div class="form-group">
                    <label><span class="dot" style="background:var(--accent3)"></span>Assignment (10%)</label>
                    <input type="number" name="assignment" min="0" max="100" step="0.01" placeholder="0 – 100" required>
                </div>

                <div class="form-group">
                    <label><span class="dot" style="background:var(--accent4)"></span>Attendance (10%)</label>
                    <input type="number" name="attendance" min="0" max="100" step="0.01" placeholder="0 – 100" required>
                </div>

                <div class="form-group">
                    <label><span class="dot" style="background:var(--accent5)"></span>Major Exam (30%)</label>
                    <input type="number" name="major_exam" min="0" max="100" step="0.01" placeholder="0 – 100" required>
                </div>

                <div class="divider"></div>

                <button type="submit" name="add_student" class="btn-submit">Save Record</button>
            </form>
        </div>

        <!-- TABLE CARD -->
        <div class="card">
            <div class="card-title">Student Records</div>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th style="color:var(--accent1)">Quiz (20%)</th>
                            <th style="color:var(--accent2)">Lab (30%)</th>
                            <th style="color:var(--accent3)">Assign (10%)</th>
                            <th style="color:var(--accent4)">Attend (10%)</th>
                            <th style="color:var(--accent5)">Exam (30%)</th>
                            <th>Final Grade</th>
                            <th>Remarks</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $count = 1;
                        if (!empty($students)):
                            foreach ($students as $row):
                                $grade = floatval($row['final_grade']);
                                if ($grade >= 90)      { $badge = "b-excellent"; $remarks = "Excellent"; }
                                elseif ($grade >= 80)  { $badge = "b-good";      $remarks = "Good"; }
                                elseif ($grade >= 75)  { $badge = "b-average";   $remarks = "Average"; }
                                else                 { $badge = "b-poor";      $remarks = "Poor"; }
                        ?>
                        <tr>
                            <td style="color:var(--muted)"><?= $count++ ?></td>
                            <td class="name-cell"><?= htmlspecialchars($row['student_name']) ?></td>
                            <td><?= floatval($row['quiz']) ?></td>
                            <td><?= floatval($row['laboratory']) ?></td>
                            <td><?= floatval($row['assignment']) ?></td>
                            <td><?= floatval($row['attendance']) ?></td>
                            <td><?= floatval($row['major_exam']) ?></td>
                            <td><span class="avg-value"><?= $grade ?></span></td>
                            <td><span class="badge <?= $badge ?>"><?= $remarks ?></span></td>
                            <td>
                                <a href="index.php?system=<?= urlencode($system_id) ?>&delete=<?= $row['id'] ?>"
                                   class="btn-delete"
                                   onclick="return confirm('Delete this record?')">del</a>
                            </td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr>
                            <td colspan="10" class="no-data">No records yet. Add a student to get started.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<footer>Grading System &copy; <?= date('Y') ?> &mdash; PHP + Supabase &mdash; System: <?= htmlspecialchars($system_id) ?></footer>

</body>
</html>