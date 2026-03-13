import xlsx from 'xlsx';

const workbook = xlsx.readFile('./Data_Base_of_Kort_Students.xls');
const sheet = workbook.Sheets[workbook.SheetNames[0]];
const data = xlsx.utils.sheet_to_json(sheet);

// Class mapping
const classMapping = {
    'Nursery': 'Nursery', 'nursery': 'Nursery',
    'Prep': 'Prep', 'prep': 'Prep',
    '1': '1', '1st': '1', 'One': '1', 'one': '1',
    '2': '2', '2nd': '2', 'Two': '2',
    '3': '3', '3rd': '3',
    '4': '4', '4th': '4',
    '5': '5', '5th': '5',
    '6': '6', '6th': '6', '6h': '6',
    '7': '7', '7th': '7',
    '8': '8', '8th': '8',
    '9': '9', '9th': '9',
    '10': '10', '10th': '10',
    '11th': '1st Year', '12th': '2nd Year',
    '1s year': '1st Year', '1st Year': '1st Year', '1st year': '1st Year',
    '2nd Year': '2nd Year', '2nd year': '2nd Year',
    'University Student': 'University',
    'Hifz': 'Hifz',
    'P.G': 'P.G',
    'Teaching @ KORT': 'Teaching',
    'Teaching ART @ KORT': 'Teaching',
    'Employee Of Kort': 'Other',
    'Not Admit In school yet': 'Other',
    'Completed': 'Other',
    'Cricket Academy': 'Other',
    'N/A': 'Other',
    '3rd / out School': 'Other',
    '3rd year': 'Other',
};

// Find students with blank or unmapped classes
const missingStudents = [];

data.forEach((row, idx) => {
    const studentName = row.Student_Name ? row.Student_Name.toString().trim() : '';
    const excelClass = row.Class ? row.Class.toString().trim() : '';

    if (!studentName) return;

    // If class is blank or unmapped
    if (!excelClass || !classMapping[excelClass]) {
        missingStudents.push({
            full_name: studentName,
            class: excelClass || 'BLANK',
            class_name: excelClass ? 'Other' : 'Other'
        });
    }
});

console.log('✓ Found ' + missingStudents.length + ' students with missing/blank classes\n');
missingStudents.forEach(s => {
    console.log(`  ${s.full_name} (class: "${s.class}")`);
});

// Save for PHP import
const fs = await import('fs');
fs.writeFileSync('missing_students.json', JSON.stringify(missingStudents, null, 2));
console.log('\nSaved to missing_students.json');
