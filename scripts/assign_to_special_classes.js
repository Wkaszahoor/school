import xlsx from 'xlsx';

// Class mapping for special categories
const specialClassMapping = {
    'University Student': 'University',
    'Hifz': 'Hifz',
    'P.G': 'P.G',
    'Teaching @ KORT': 'Teaching',
    'Teaching ART @ KORT': 'Teaching',
    'Employee Of Kort': 'Other',
    'Not Admit In school yet': 'Other',
    'Completed': 'Other',
    'Cricket Academy': 'Other',
    '3rd / out School': 'Other',
    '3rd year': 'Other',
    'N/A': 'Other',
};

// Read Excel
const workbook = xlsx.readFile('./Data_Base_of_Kort_Students.xls');
const sheet = workbook.Sheets[workbook.SheetNames[0]];
const data = xlsx.utils.sheet_to_json(sheet);

// Build mapping of special students
const specialStudents = {};

data.forEach(row => {
    const studentName = row.Student_Name ? row.Student_Name.toString().trim() : '';
    const excelClass = row.Class ? row.Class.toString().trim() : '';

    if (!studentName || !excelClass) return;

    if (specialClassMapping[excelClass]) {
        const nameKey = studentName.toLowerCase();
        specialStudents[nameKey] = specialClassMapping[excelClass];
    }
});

// Save mapping
const fs = await import('fs');
fs.writeFileSync('special_class_assignments.json', JSON.stringify(specialStudents, null, 2));

console.log('✓ Extracted ' + Object.keys(specialStudents).length + ' special category students');
console.log('\nAssignments:');
const counts = {};
Object.values(specialStudents).forEach(cls => {
    counts[cls] = (counts[cls] || 0) + 1;
});
Object.entries(counts).forEach(([cls, count]) => {
    console.log('  ' + cls + ': ' + count + ' students');
});
console.log('\nSaved to special_class_assignments.json');
