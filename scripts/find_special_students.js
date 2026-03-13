import xlsx from 'xlsx';

// Read Excel to find students with special classes
const workbook = xlsx.readFile('./Data_Base_of_Kort_Students.xls');
const sheet = workbook.Sheets[workbook.SheetNames[0]];
const data = xlsx.utils.sheet_to_json(sheet);

const specialClasses = new Map();

data.forEach(row => {
    const studentName = row.Student_Name ? row.Student_Name.toString().trim() : '';
    const excelClass = row.Class ? row.Class.toString().trim() : '';

    if (!studentName || !excelClass) return;

    // Check if it's a special/unmapped class
    const specialList = [
        'University Student', 'Hifz', 'Completed', 'Cricket Academy', 'Employee Of Kort',
        'Not Admit In school yet', 'P.G', 'Teaching @ KORT', 'Teaching ART @ KORT',
        '3rd / out School', '3rd year', 'N/A'
    ];

    if (specialList.includes(excelClass)) {
        if (!specialClasses.has(excelClass)) {
            specialClasses.set(excelClass, []);
        }
        specialClasses.get(excelClass).push(studentName);
    }
});

console.log('📊 Special Categories Distribution:\n');
let total = 0;
Array.from(specialClasses.entries())
    .sort((a, b) => b[1].length - a[1].length)
    .forEach(([cls, students]) => {
        console.log(`  ${cls}: ${students.length} students`);
        total += students.length;
    });

console.log(`\nTotal: ${total} students with special categories`);
