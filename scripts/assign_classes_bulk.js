import xlsx from 'xlsx';

// Map Excel class names to database class names
const classMapping = {
    'Nursery': 'Nursery', 'nursery': 'Nursery',
    'Prep': 'Prep', 'prep': 'Prep',
    'Prep (A)': 'Prep',
    '1': '1', '1st': '1', '1st (A)': '1', 'One': '1', 'one': '1',
    '2': '2', '2nd': '2', 'Two': '2',
    '3': '3', '3rd': '3', '3rd (A)': '3',
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
};

try {
    console.log('🔄 Reading Excel file...\n');

    const workbook = xlsx.readFile('./Data_Base_of_Kort_Students.xls');
    const sheet = workbook.Sheets[workbook.SheetNames[0]];
    const data = xlsx.utils.sheet_to_json(sheet);

    // Build mapping of student names to classes
    const studentClassMap = {};
    let validRecords = 0;
    let skipped = 0;

    data.forEach((row, idx) => {
        const studentName = row.Student_Name ? row.Student_Name.toString().trim() : '';
        const excelClass = row.Class ? row.Class.toString().trim() : '';

        if (!studentName || !excelClass) {
            skipped++;
            return;
        }

        const mappedClass = classMapping[excelClass];
        if (!mappedClass) {
            skipped++;
            return;
        }

        // Store the mapping (use lowercase name for fuzzy matching)
        const nameKey = studentName.toLowerCase();
        if (!studentClassMap[nameKey]) {
            studentClassMap[nameKey] = mappedClass;
            validRecords++;
        }
    });

    console.log(`✓ Extracted ${validRecords} valid student-class mappings from Excel`);
    console.log(`⏭️  Skipped ${skipped} records (missing data or unmapped class)\n`);

    // Save the mapping to a JSON file for PHP to process
    const fs = await import('fs');
    fs.writeFileSync('class_assignments.json', JSON.stringify(studentClassMap, null, 2));
    console.log('✓ Student-class mappings saved to class_assignments.json');
    console.log('\nNow run the PHP import script to update the database.');
} catch (e) {
    console.error('❌ Error:', e.message);
    process.exit(1);
}
