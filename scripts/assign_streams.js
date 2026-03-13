import xlsx from 'xlsx';

// Stream mapping - mapping Excel course/stream to our subject groups
const streamMapping = {
    'Science': 'Science',
    'science': 'Science',
    'SCIENCE': 'Science',
    'Arts': 'Arts',
    'arts': 'Arts',
    'ARTS': 'Arts',
    'Commerce': 'Commerce',
    'commerce': 'Commerce',
    'COMMERCE': 'Commerce',
    'General': 'General',
    'general': 'General',
    'GENERAL': 'General',
};

// Read Excel
const workbook = xlsx.readFile('./Data_Base_of_Kort_Students.xls');
const sheet = workbook.Sheets[workbook.SheetNames[0]];
const data = xlsx.utils.sheet_to_json(sheet);

// Classes 9-12
const targetClasses = ['9', '9th', '10', '10th', '1st Year', '1st year', '2nd Year', '2nd year', '11th', '12th'];

// Find students in classes 9-12 with stream info
const streamAssignments = {};
let found = 0;

data.forEach(row => {
    const studentName = row.Student_Name ? row.Student_Name.toString().trim() : '';
    const excelClass = row.Class ? row.Class.toString().trim() : '';
    const excelCourse = row.Course ? row.Course.toString().trim() : '';

    if (!studentName || !excelClass || !excelCourse) return;

    // Check if student is in target classes
    if (!targetClasses.includes(excelClass)) return;

    const stream = streamMapping[excelCourse];
    if (stream) {
        const nameKey = studentName.toLowerCase();
        streamAssignments[nameKey] = {
            original_name: studentName,
            class: excelClass,
            stream: stream,
            excel_course: excelCourse
        };
        found++;
    }
});

console.log('✓ Found ' + found + ' students in classes 9-12 with stream info\n');
console.log('Stream distribution:');
const counts = {};
Object.values(streamAssignments).forEach(item => {
    counts[item.stream] = (counts[item.stream] || 0) + 1;
});
Object.entries(counts).forEach(([stream, count]) => {
    console.log('  ' + stream + ': ' + count + ' students');
});

// Save mapping
const fs = await import('fs');
fs.writeFileSync('stream_assignments.json', JSON.stringify(streamAssignments, null, 2));
console.log('\nSaved to stream_assignments.json');
