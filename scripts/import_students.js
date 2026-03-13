import xlsx from 'xlsx';

// Helper to convert Excel date format to ISO date
function excelDateToISO(excelDate) {
    if (!excelDate || typeof excelDate !== 'number') return null;
    const date = new Date((excelDate - 25569) * 86400 * 1000);
    return date.toISOString().split('T')[0];
}

// Enhanced class mapping
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

const skipClasses = new Set([
    'Completed', 'Cricket Academy', 'Hifz', 'Employee Of Kort',
    'Not Admit In school yet', 'University Student', 'P.G',
    'Teaching @ KORT', 'Teaching ART @ KORT', '3rd / out School', '3rd year', 'N/A'
]);

try {
    console.log('🔄 Processing Excel file...\n');

    const workbook = xlsx.readFile('./Data_Base_of_Kort_Students.xls');
    const sheet = workbook.Sheets[workbook.SheetNames[0]];
    const data = xlsx.utils.sheet_to_json(sheet);

    // Process students
    let imported = 0;
    let skipped = 0;
    const errors = [];

    const students = [];

    data.forEach((row, idx) => {
        const excelClass = row.Class ? row.Class.toString().trim() : '';

        // Skip students with unmapped/special classes
        if (skipClasses.has(excelClass)) {
            skipped++;
            console.log(`⏭️  Row ${idx + 2}: Skipped (class: "${excelClass}")`);
            return;
        }

        const mappedClass = classMapping[excelClass];
        if (!mappedClass) {
            skipped++;
            console.log(`⚠️  Row ${idx + 2}: Skipped (unmapped class: "${excelClass}")`);
            return;
        }

        // Build student object
        const student = {
            full_name: row.Student_Name ? row.Student_Name.toString().trim() : '',
            admission_no: 'STU' + (row.ID ? String(row.ID).padStart(4, '0') : '0000'),
            student_cnic: row.Student_CNIC || '',
            father_name: row.Father_Name || '',
            father_cnic: row.Father_CNIC || '',
            mother_name: row.Mother_Name || '',
            mother_cnic: row.Mother_CNIC || '',
            guardian_name: row["Guardian's_Name"] || '',
            guardian_cnic: row["Guardian's_CNIC"] || '',
            guardian_address: row.Address || '',
            phone: row.Contact_Number || '',
            dob: excelDateToISO(row.Date_of_Birth),
            favorite_color: row.Favorite_color || '',
            favorite_food: row.Favorite_Food || '',
            favorite_subject: row.Favorite_Subject || '',
            ambition: row.Ambition || '',
            gender: (row.Gender || 'Male').toLowerCase().includes('female') ? 'female' : 'male',
            is_active: (row.Status || '').toLowerCase() === 'active' ? 1 : 0,
            group_stream: row.Course || '',
            semester: row.Semester || '',
            join_date_kort: excelDateToISO(row.Joining_Date),
            class_name: mappedClass,  // Will be resolved to class_id by PHP
        };

        // Validate required fields
        if (!student.full_name || !student.dob || !student.gender) {
            errors.push(`Row ${idx + 2}: Missing required fields`);
            skipped++;
            return;
        }

        students.push(student);
    });

    if (students.length === 0) {
        console.log('\n❌ No valid students to import');
        process.exit(1);
    }

    console.log(`\n📊 Ready to import ${students.length} students (skipped: ${skipped})`);
    if (errors.length > 0) {
        console.log('\n⚠️  Errors:');
        errors.slice(0, 5).forEach(err => console.log('  - ' + err));
        if (errors.length > 5) console.log(`  ... and ${errors.length - 5} more`);
    }

    // Save to JSON file for manual review
    const fs = await import('fs');
    fs.writeFileSync('import_data.json', JSON.stringify(students, null, 2));
    console.log('\n✓ Student data saved to import_data.json');
    console.log('  You can now use the Receptionist import page to upload this data');

} catch (e) {
    console.error('❌ Error:', e.message);
    process.exit(1);
}
