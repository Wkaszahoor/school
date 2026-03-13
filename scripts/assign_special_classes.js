import xlsx from 'xlsx';

// Class mapping for special categories
const specialClassMapping = {
    'University Student': '2nd Year',
    'Hifz': '1st Year',
    'P.G': '2nd Year',
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
fs.writeFileSync('special_assignments.json', JSON.stringify(specialStudents, null, 2));

console.log('✓ Extracted ' + Object.keys(specialStudents).length + ' special category students');
console.log('  University Students → 2nd Year');
console.log('  Hifz → 1st Year');
console.log('  P.G → 2nd Year');
console.log('\nSaved to special_assignments.json');
