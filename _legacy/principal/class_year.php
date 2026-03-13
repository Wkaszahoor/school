<?php
ob_start();
require_once __DIR__ . '/../auth.php';
auth_require_permission('class_year', 'view', 'index.php');
include '../db.php';
include './partials/topbar.php';

$classes = [];
$qClasses = $conn->query("
    SELECT c.id, c.class, c.academic_year,
           COUNT(DISTINCT s.id) AS student_count,
           COUNT(DISTINCT t.id) AS teacher_assigned_count
    FROM classes c
    LEFT JOIN students s
        ON s.class = c.class
        AND IFNULL(s.academic_year, '') = IFNULL(c.academic_year, '')
    LEFT JOIN teachers t
        ON t.class_assigned = c.class
    GROUP BY c.id, c.class, c.academic_year
    ORDER BY c.academic_year DESC, c.class ASC
");
while ($row = $qClasses->fetch_assoc()) {
    $classes[] = $row;
}
?>
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Class-Year Report</h1>
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">All Class-Year Records (Read Only)</h6>
        </div>
        <div class="card-body">
            <div class="form-row mb-3">
                <div class="form-group col-md-4">
                    <label for="class_year_search_class">Search by Class</label>
                    <input type="text" id="class_year_search_class" class="form-control" placeholder="Type class name">
                </div>
                <div class="form-group col-md-4">
                    <label for="class_year_search_year">Search by Academic Year</label>
                    <input type="text" id="class_year_search_year" class="form-control" placeholder="Type academic year">
                </div>
                <div class="form-group col-md-2 d-flex align-items-end">
                    <button type="button" id="class_year_search_clear" class="btn btn-secondary w-100">Clear</button>
                </div>
            </div>
            <div class="table-responsive">
                <table id="class_year_table" class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Class</th>
                        <th>Academic Year</th>
                        <th>Students</th>
                        <th>Assigned Teachers</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (!empty($classes)): foreach ($classes as $class): ?>
                        <tr>
                            <td><?php echo (int)$class['id']; ?></td>
                            <td><?php echo htmlspecialchars((string)$class['class'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string)($class['academic_year'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo (int)$class['student_count']; ?></td>
                            <td><?php echo (int)$class['teacher_assigned_count']; ?></td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr class="no-data-row"><td colspan="5" class="text-center">No classes found.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script>
function makeTableSortable(tableId) {
    var table = document.getElementById(tableId);
    if (!table) return;
    var headers = table.querySelectorAll('thead th');
    var state = {};
    headers.forEach(function (th, index) {
        th.style.cursor = 'pointer';
        th.title = 'Click to sort';
        th.addEventListener('click', function () {
            var tbody = table.querySelector('tbody');
            if (!tbody) return;
            var rows = Array.prototype.slice.call(tbody.querySelectorAll('tr')).filter(function (row) {
                return !row.classList.contains('no-data-row');
            });
            if (rows.length === 0) return;
            var direction = state[index] === 'asc' ? 'desc' : 'asc';
            state = {};
            state[index] = direction;
            rows.sort(function (rowA, rowB) {
                var a = (rowA.children[index] ? rowA.children[index].innerText : '').trim();
                var b = (rowB.children[index] ? rowB.children[index].innerText : '').trim();
                var numA = parseFloat(a.replace(/[^0-9.\-]/g, ''));
                var numB = parseFloat(b.replace(/[^0-9.\-]/g, ''));
                if (!isNaN(numA) && !isNaN(numB)) return numA - numB;
                return a.localeCompare(b, undefined, { numeric: true, sensitivity: 'base' });
            });
            if (direction === 'desc') rows.reverse();
            rows.forEach(function (row) { tbody.appendChild(row); });
        });
    });
}

function bindClassYearSearch() {
    var table = document.getElementById('class_year_table');
    var classInput = document.getElementById('class_year_search_class');
    var yearInput = document.getElementById('class_year_search_year');
    var clearBtn = document.getElementById('class_year_search_clear');
    if (!table || !classInput || !yearInput || !clearBtn) return;
    var tbody = table.querySelector('tbody');
    var noDataRow = tbody ? tbody.querySelector('tr.no-data-row') : null;
    function applyFilter() {
        var classQuery = classInput.value.toLowerCase().trim();
        var yearQuery = yearInput.value.toLowerCase().trim();
        var rows = Array.prototype.slice.call(tbody.querySelectorAll('tr'));
        var visible = 0;
        rows.forEach(function (row) {
            if (row.classList.contains('no-data-row')) return;
            var className = (row.children[1] ? row.children[1].innerText : '').toLowerCase();
            var yearValue = (row.children[2] ? row.children[2].innerText : '').toLowerCase();
            var show = className.indexOf(classQuery) !== -1 && yearValue.indexOf(yearQuery) !== -1;
            row.style.display = show ? '' : 'none';
            if (show) visible++;
        });
        if (noDataRow) noDataRow.style.display = visible === 0 ? '' : 'none';
    }
    classInput.addEventListener('input', applyFilter);
    yearInput.addEventListener('input', applyFilter);
    clearBtn.addEventListener('click', function () {
        classInput.value = '';
        yearInput.value = '';
        applyFilter();
    });
}

makeTableSortable('class_year_table');
bindClassYearSearch();
</script>
<?php include './partials/footer.php'; ?>
