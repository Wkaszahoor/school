import xlsx from 'xlsx';

// Class mapping
const classMapping = {
    'Nursery': 'Nursery',
    'Prep': 'Prep',
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
    '11th': '1st Year', '11': '1st Year',
    '12th': '2nd Year', '12': '2nd Year',
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
};

// Remaining students
const remaining = [
    { name: 'Muhammad Bilal', admNo: 'KRT0078' },
    { name: 'Muhammad Ali', admNo: 'KRT0097' },
    { name: 'Muhammad Ali', admNo: 'KRT0100' },
    { name: 'Muhammad Ali', admNo: 'KRT0107' },
    { name: 'Muhammad Zeeshan', admNo: 'KRT0112' },
    { name: 'Muhammad Ali', admNo: 'KRT0424' },
    { name: 'Muhammad Ibrahim', admNo: 'KRT0495' },
    { name: 'Muhammad Ibrahim', admNo: 'KRT0500' },
    { name: 'Muhammad Ibrahim', admNo: 'KRTKORT535' }
];

// Read Excel
const workbook = xlsx.readFile('./Data_Base_of_Kort_Students.xls');
const sheet = workbook.Sheets[workbook.SheetNames[0]];
const data = xlsx.utils.sheet_to_json(sheet);

// Find matches by fuzzy name matching
const matches = {};
let found = 0;

remaining.forEach(unassigned => {
    const nameKey = unassigned.name.toLowerCase();

    data.forEach(row => {
        const studentName = row.Student_Name ? row.Student_Name.toString().trim() : '';
        const excelClass = row.Class ? row.Class.toString().trim() : '';

        if (!studentName || !excelClass) return;

        const nameFromExcel = studentName.toLowerCase();

        // Fuzzy matching - if names are similar enough or Excel name contains the database name
        if (
            nameFromExcel.includes(nameKey) &&
            !matches[unassigned.admNo]
        ) {
            const mappedClass = classMapping[excelClass];
            if (mappedClass) {
                matches[unassigned.admNo] = {
                    original_name: unassigned.name,
                    matched_excel_name: studentName,
                    class: mappedClass,
                    excel_class: excelClass
                };
                found++;
            }
        }
    });
});

// For unmatched, assign to most common class (Class 6)
remaining.forEach(unassigned => {
    if (!matches[unassigned.admNo]) {
        matches[unassigned.admNo] = {
            original_name: unassigned.name,
            matched_excel_name: 'NOT FOUND',
            class: '6',
            excel_class: 'DEFAULT',
            reason: 'Assigned to most common class (6)'
        };
    }
});

// Save mapping
const fs = await import('fs');
fs.writeFileSync('final_assignments.json', JSON.stringify(matches, null, 2));

console.log('✓ Found/assigned ' + Object.keys(matches).length + ' students');
console.log('\nAssignments:');
Object.entries(matches).forEach(([admNo, data]) => {
    console.log(`  ${data.original_name} (${admNo}) → ${data.class}`);
    if (data.reason) console.log(`    (${data.reason})`);
});
console.log('\nSaved to final_assignments.json');
