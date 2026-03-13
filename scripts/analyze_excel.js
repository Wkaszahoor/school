import xlsx from 'xlsx';

// Map Excel class names to database class names
const classMapping = {
    'Nursery': 'Nursery',
    'Prep': 'Prep',
    'Prep (A)': 'Prep',
    '1': '1', '1st': '1', '1st (A)': '1',
    '2': '2', '2nd': '2',
    '3': '3', '3rd': '3',
    '4': '4', '4th': '4',
    '5': '5', '5th': '5',
    '6': '6', '6th': '6',
    '7': '7', '7th': '7',
    '8': '8', '8th': '8',
    '9': '9', '9th': '9',
    '10': '10', '10th': '10',
    '1st year': '1st Year', '1st Year': '1st Year',
    '2nd year': '2nd Year', '2nd Year': '2nd Year',
};

try {
    const workbook = xlsx.readFile('./Data_Base_of_Kort_Students.xls');
    const sheet = workbook.Sheets[workbook.SheetNames[0]];
    const data = xlsx.utils.sheet_to_json(sheet);

    // Analyze class distribution
    const classDistribution = {};
    const unmappedClasses = new Set();

    data.forEach(row => {
        const excelClass = row.Class ? row.Class.toString().trim() : null;
        if(!excelClass) return;

        const mapped = classMapping[excelClass];
        if(!mapped) {
            unmappedClasses.add(excelClass);
        }
        classDistribution[excelClass] = (classDistribution[excelClass] || 0) + 1;
    });

    console.log('✓ Excel file analysis:');
    console.log('  Total students: ' + data.length);
    console.log('\n  Class distribution:');
    Object.entries(classDistribution).sort().forEach(([cls, count]) => {
        const mapped = classMapping[cls] ? ' → ' + classMapping[cls] : ' (UNMAPPED)';
        console.log('    ' + cls + ': ' + count + ' students' + mapped);
    });

    if(unmappedClasses.size > 0) {
        console.log('\n  ⚠️  Unmapped classes that need mapping:');
        Array.from(unmappedClasses).sort().forEach(cls => {
            console.log('    - "' + cls + '"');
        });
    }
} catch(e) {
    console.error('Error: ' + e.message);
}
