import xlsx from 'xlsx';

// Helper to convert Excel date format to ISO date
function excelDateToISO(excelDate) {
    if (!excelDate || typeof excelDate !== 'number') return null;
    const date = new Date((excelDate - 25569) * 86400 * 1000);
    return date.toISOString().split('T')[0];
}

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
};

try {
    console.log('🔄 Reading Excel file...\n');

    const workbook = xlsx.readFile('./Data_Base_of_Kort_Students.xls');
    const sheet = workbook.Sheets[workbook.SheetNames[0]];
    const data = xlsx.utils.sheet_to_json(sheet);

    console.log('📊 Processing ' + data.length + ' students...\n');

    const students = [];
    let skipped = 0;

    data.forEach((row, idx) => {
        const studentName = row.Student_Name ? row.Student_Name.toString().trim() : '';
        const excelClass = row.Class ? row.Class.toString().trim() : '';
        const dob = row.Date_of_Birth;
        const gender = row.Gender ? row.Gender.toString().toLowerCase() : 'male';

        if (!studentName || !dob) {
            skipped++;
            return;
        }

        const mappedClass = classMapping[excelClass] || 'Other';

        const student = {
            full_name: studentName,
            admission_no: 'KRT' + String(row.ID || idx).padStart(4, '0'),
            student_cnic: row.Student_CNIC || '',
            father_name: row.Father_Name || '',
            father_cnic: row.Father_CNIC || '',
            mother_name: row.Mother_Name || '',
            mother_cnic: row.Mother_CNIC || '',
            guardian_name: row["Guardian's_Name"] || '',
            guardian_cnic: row["Guardian's_CNIC"] || '',
            guardian_address: row.Address || '',
            phone: row.Contact_Number || '',
            dob: excelDateToISO(dob),
            gender: gender.includes('female') ? 'female' : 'male',
            class_name: mappedClass,
            group_stream: row.Course || '',
            semester: row.Semester || '',
            join_date_kort: excelDateToISO(row.Joining_Date),
            favorite_color: row.Favorite_color || '',
            favorite_food: row.Favorite_Food || '',
            favorite_subject: row.Favorite_Subject || '',
            ambition: row.Ambition || '',
            is_active: (row.Status || '').toLowerCase() === 'active' ? 1 : 0,
            reason_left_kort: row.Reason_LeftKORT || '',
        };

        students.push(student);
    });

    console.log('✓ Processed ' + students.length + ' students');
    if (skipped > 0) {
        console.log('⏭️  Skipped ' + skipped + ' (missing name or DOB)\n');
    }

    // Save to JSON for PHP import
    const fs = await import('fs');
    fs.writeFileSync('complete_import.json', JSON.stringify(students, null, 2));

    console.log('✓ Saved to complete_import.json');
    console.log('\nReady for database import!');
} catch (e) {
    console.error('❌ Error: ' + e.message);
    process.exit(1);
}
