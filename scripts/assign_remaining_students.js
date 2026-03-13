import xlsx from 'xlsx';

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
    '3rd / out School': 'Other',
    '3rd year': 'Other',
    'N/A': 'Other',
};

// Unassigned students list
const unassignedNames = [
    'Abdul Rehman', 'Abdullah', 'Muhammad Bilal', 'Muhammad Ali', 'Muhammad Hassan',
    'Muhammad Zeeshan', 'Muhammad Arman', 'Emaan Fatima', 'Maryam Bibi', 'Jaweria Bibi',
    'Maria Bibi', 'Muhammad Shehryar', 'Muhammad Ibrahim', 'Eman Fatima'
];

// Read Excel
const workbook = xlsx.readFile('./Data_Base_of_Kort_Students.xls');
const sheet = workbook.Sheets[workbook.SheetNames[0]];
const data = xlsx.utils.sheet_to_json(sheet);

// Find matches
const matches = {};
let found = 0;

data.forEach(row => {
    const studentName = row.Student_Name ? row.Student_Name.toString().trim() : '';
    const excelClass = row.Class ? row.Class.toString().trim() : '';

    if (!studentName || !excelClass) return;

    // Check if this is one of our unassigned students
    const nameKey = studentName.toLowerCase();

    unassignedNames.forEach(unassignedName => {
        const unassignedKey = unassignedName.toLowerCase();

        // Exact match or contains match
        if (nameKey === unassignedKey || nameKey.includes(unassignedKey) || unassignedKey.includes(nameKey)) {
            const mappedClass = classMapping[excelClass];
            if (mappedClass) {
                if (!matches[nameKey]) {
                    matches[nameKey] = {
                        original_name: studentName,
                        class: mappedClass,
                        excel_class: excelClass
                    };
                    found++;
                }
            }
        }
    });
});

// Save mapping
const fs = await import('fs');
fs.writeFileSync('remaining_assignments.json', JSON.stringify(matches, null, 2));

console.log('✓ Found ' + found + ' matches for unassigned students');
console.log('\nMatches found:');
Object.entries(matches).forEach(([name, data]) => {
    console.log('  ' + data.original_name + ' → ' + data.class + ' (from: ' + data.excel_class + ')');
});
console.log('\nSaved to remaining_assignments.json');
