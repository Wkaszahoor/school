import{r as s,j as t,H as g}from"./app-Cdgy570w.js";/* empty css            */function b({title:r,titleId:i,...a},d){return s.createElement("svg",Object.assign({xmlns:"http://www.w3.org/2000/svg",fill:"none",viewBox:"0 0 24 24",strokeWidth:1.5,stroke:"currentColor","aria-hidden":"true","data-slot":"icon",ref:d,"aria-labelledby":i},a),r?s.createElement("title",{id:i},r):null,s.createElement("path",{strokeLinecap:"round",strokeLinejoin:"round",d:"M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Z"}))}const h=s.forwardRef(b),o=r=>r>=80?"text-emerald-600":r>=70?"text-emerald-500":r>=60?"text-blue-600":r>=50?"text-amber-600":"text-red-600",u=r=>r>=80||r>=70?"bg-emerald-50":r>=60?"bg-blue-50":r>=50?"bg-amber-50":"bg-red-50",y=r=>r==="PASS"?"bg-gradient-to-r from-emerald-100 to-green-100":"bg-gradient-to-r from-red-100 to-rose-100",f=r=>r==="PASS"?"text-emerald-800":"text-red-800",j=r=>r.split(" ").map(i=>i[0]).join("").toUpperCase().slice(0,2);function k({reportData:r,filters:i,examTypes:a,terms:d,classes:p}){const[w,x]=s.useState(null),c=()=>window.print(),m=i.class_id&&p.find(e=>e.id===Number(i.class_id))?`${p.find(e=>e.id===Number(i.class_id))?.class}${p.find(e=>e.id===Number(i.class_id))?.section?` — ${p.find(e=>e.id===Number(i.class_id))?.section}`:""}`:"All Classes";return t.jsxs(t.Fragment,{children:[t.jsx(g,{title:"Report Cards",children:t.jsx("style",{children:`
                    @media print {
                        * {
                            box-shadow: none !important;
                            margin: 0;
                            padding: 0;
                        }
                        html { margin: 0; padding: 0; }
                        body { margin: 0; padding: 0; background: white !important; }
                        .no-print { display: none !important; }

                        .report-card {
                            page-break-after: always;
                            page-break-inside: avoid;
                            box-shadow: none !important;
                            margin: 0 !important;
                            padding: 0 !important;
                            border-radius: 0 !important;
                            border: none;
                            background: white !important;
                            width: 100%;
                        }
                        .report-card:last-child { page-break-after: avoid; }

                        .report-card > div {
                            page-break-inside: avoid;
                        }

                        /* Header styling for print */
                        .report-card > div:first-child {
                            margin-bottom: 0;
                            padding: 20mm 15mm !important;
                            background: white !important;
                            border: 1px solid #333 !important;
                            color: #000 !important;
                        }

                        /* Student info section */
                        .report-card > div:nth-child(2) {
                            margin-bottom: 0;
                            padding: 10mm 15mm !important;
                            background: white !important;
                            border: 1px solid #ddd !important;
                        }

                        /* Marks table section */
                        .report-card > div:nth-child(3) {
                            margin-bottom: 0;
                            padding: 10mm 15mm !important;
                            background: white !important;
                        }

                        table {
                            width: 100% !important;
                            border-collapse: collapse !important;
                        }

                        th, td {
                            border: 1px solid #333 !important;
                            padding: 6px 8px !important;
                            text-align: left;
                        }

                        th {
                            background: #333 !important;
                            color: white !important;
                            font-weight: bold !important;
                        }

                        /* Remarks section */
                        .report-card > div:nth-child(4) {
                            margin-bottom: 0;
                            padding: 10mm 15mm !important;
                            background: white !important;
                            border: 1px solid #ddd !important;
                            page-break-inside: avoid;
                        }

                        /* Pass/Fail badge section */
                        .report-card > div:nth-child(5) {
                            margin-bottom: 0;
                            padding: 8mm 15mm !important;
                            background: white !important;
                            border: 1px solid #ddd !important;
                        }

                        /* Signature section */
                        .report-card > div:last-child {
                            margin-bottom: 0;
                            padding: 10mm 15mm !important;
                            background: white !important;
                            border: 1px solid #ddd !important;
                            page-break-inside: avoid;
                        }

                        img {
                            max-width: 100%;
                            height: auto;
                        }

                        .text-gradient-to-r,
                        .bg-gradient-to-r,
                        .bg-gradient-to-br {
                            background: white !important;
                        }

                        h3 {
                            margin: 0 0 8px 0 !important;
                            font-size: 14px !important;
                        }

                        p {
                            margin: 2px 0 !important;
                            font-size: 11px !important;
                        }
                    }

                    @page {
                        size: A4 portrait;
                        margin: 10mm;
                    }

                    @media screen {
                        body { background: linear-gradient(135deg, #f5f7fa 0%, #e9ecef 100%); }
                        .report-card { background: white; margin: 24px 0; }
                    }
                `})}),t.jsxs("div",{className:"no-print sticky top-0 z-50 bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 text-white px-8 py-4 flex items-center justify-between shadow-2xl backdrop-blur-sm",children:[t.jsxs("div",{className:"flex items-center gap-6",children:[t.jsxs("div",{children:[t.jsx("p",{className:"font-bold text-lg tracking-wide",children:"KORT School — Report Cards"}),t.jsxs("p",{className:"text-slate-300 text-xs mt-2 space-x-2",children:[t.jsx("span",{children:a[i.exam_type]}),t.jsx("span",{className:"text-slate-500",children:"•"}),t.jsx("span",{children:d[i.term]}),t.jsx("span",{className:"text-slate-500",children:"•"}),t.jsx("span",{children:i.academic_year}),t.jsx("span",{className:"text-slate-500",children:"•"}),t.jsx("span",{children:m})]})]}),t.jsx("div",{className:"text-slate-300 text-sm border-l border-slate-600 pl-6",children:t.jsxs("span",{className:"bg-slate-700 px-3 py-1 rounded-full text-xs font-semibold",children:[r.length," student",r.length!==1?"s":""]})})]}),t.jsxs("button",{onClick:c,className:"bg-gradient-to-r from-blue-500 to-blue-600 text-white px-6 py-2.5 rounded-lg font-semibold text-sm hover:from-blue-600 hover:to-blue-700 active:scale-95 flex items-center gap-2 transition-all duration-200 shadow-lg hover:shadow-xl",children:[t.jsx(h,{className:"w-5 h-5"}),"Print / Save as PDF"]})]}),t.jsx("div",{className:"p-6 min-h-screen print:p-0",children:t.jsx("div",{className:"max-w-5xl mx-auto print:max-w-full",children:r.length===0?t.jsxs("div",{className:"bg-white rounded-2xl p-16 text-center text-gray-500 shadow-lg",children:[t.jsx("div",{className:"inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 mb-6",children:t.jsx("svg",{className:"w-8 h-8 text-gray-400",fill:"none",stroke:"currentColor",viewBox:"0 0 24 24",children:t.jsx("path",{strokeLinecap:"round",strokeLinejoin:"round",strokeWidth:2,d:"M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"})})}),t.jsx("p",{className:"text-xl font-semibold text-gray-700",children:"No approved results found"}),t.jsx("p",{className:"text-gray-500 mt-2",children:"Check back once results are approved by the principal."})]}):r.map(e=>t.jsxs("div",{className:"report-card rounded-2xl shadow-xl overflow-hidden mb-8 transition-all duration-300 hover:shadow-2xl",onMouseEnter:()=>x(e.student.id),onMouseLeave:()=>x(null),children:[t.jsxs("div",{className:"bg-gradient-to-br from-indigo-900 via-blue-900 to-blue-800 text-white px-10 py-8 relative overflow-hidden print:bg-white print:text-black print:py-4 print:px-6",children:[t.jsx("div",{className:"absolute top-0 right-0 w-40 h-40 bg-blue-500 rounded-full opacity-10 transform translate-x-20 -translate-y-20 print:hidden"}),t.jsx("div",{className:"absolute bottom-0 left-0 w-32 h-32 bg-indigo-400 rounded-full opacity-10 transform -translate-x-16 translate-y-16 print:hidden"}),t.jsxs("div",{className:"text-center relative z-10",children:[t.jsx("div",{className:"flex justify-center mb-4 print:mb-2",children:t.jsx("div",{className:"h-16 w-16 rounded-full bg-white flex items-center justify-center text-2xl font-black text-blue-900 print:h-12 print:w-12 print:text-lg",children:"K"})}),t.jsx("p",{className:"font-black text-sm tracking-widest text-blue-100 print:text-gray-900 print:text-xs",children:"KORT SCHOOL MANAGEMENT SYSTEM"}),t.jsx("p",{className:"text-xs text-blue-200 mt-1 print:text-gray-600 print:mt-0",children:"Providing quality healthcare and hope"}),t.jsx("p",{className:"text-3xl font-black mt-5 tracking-tight print:text-2xl print:mt-2",children:"PROGRESS REPORT CARD"}),t.jsxs("div",{className:"text-sm mt-4 text-blue-100 space-y-1.5 print:text-xs print:text-gray-700 print:mt-2 print:space-y-0.5",children:[t.jsxs("p",{className:"font-semibold print:text-gray-900",children:[a[i.exam_type]," Examination | ",d[i.term]," | Session ",i.academic_year]}),t.jsxs("p",{className:"text-blue-200 print:text-gray-600",children:["Date: ",new Date().toLocaleDateString("en-GB",{day:"numeric",month:"long",year:"numeric"})]})]})]})]}),t.jsx("div",{className:"border-b-2 border-gray-100 bg-gradient-to-br from-gray-50 to-gray-100 print:bg-white print:border-gray-300 print:py-2 print:px-6",children:t.jsxs("div",{className:"grid grid-cols-1 md:grid-cols-4 gap-8 p-10 print:gap-4 print:p-0",children:[t.jsx("div",{className:"flex justify-center md:justify-start",children:e.student.photo?t.jsx("img",{src:`/storage/${e.student.photo}`,alt:e.student.full_name,className:"w-28 h-28 rounded-xl border-4 border-white object-cover shadow-lg print:w-20 print:h-20 print:rounded-sm print:border-2"}):t.jsx("div",{className:"w-28 h-28 rounded-xl bg-gradient-to-br from-indigo-600 to-blue-600 text-white flex items-center justify-center font-black text-3xl border-4 border-white shadow-lg print:w-20 print:h-20 print:rounded-sm print:text-lg print:border-2",children:j(e.student.full_name)})}),t.jsx("div",{className:"md:col-span-3",children:t.jsxs("div",{className:"grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 print:grid-cols-2 print:gap-2",children:[t.jsxs("div",{className:"bg-white rounded-lg p-4 shadow-sm print:bg-white print:rounded-none print:p-1.5 print:border print:border-gray-300",children:[t.jsx("p",{className:"text-gray-500 text-xs font-bold uppercase tracking-wide print:text-gray-600 print:text-xs print:tracking-normal",children:"Name"}),t.jsx("p",{className:"font-bold text-gray-900 text-lg mt-1 print:text-sm print:mt-0",children:e.student.full_name})]}),t.jsxs("div",{className:"bg-white rounded-lg p-4 shadow-sm print:bg-white print:rounded-none print:p-1.5 print:border print:border-gray-300",children:[t.jsx("p",{className:"text-gray-500 text-xs font-bold uppercase tracking-wide print:text-gray-600 print:text-xs print:tracking-normal",children:"Admission No."}),t.jsx("p",{className:"font-bold text-gray-900 text-lg mt-1 print:text-sm print:mt-0",children:e.student.admission_no})]}),t.jsxs("div",{className:"bg-white rounded-lg p-4 shadow-sm print:bg-white print:rounded-none print:p-1.5 print:border print:border-gray-300",children:[t.jsx("p",{className:"text-gray-500 text-xs font-bold uppercase tracking-wide print:text-gray-600 print:text-xs print:tracking-normal",children:"Class"}),t.jsx("p",{className:"font-bold text-gray-900 text-lg mt-1 print:text-sm print:mt-0",children:e.student.class?`${e.student.class.class}${e.student.class.section?` — ${e.student.class.section}`:""}`:"N/A"})]}),t.jsxs("div",{className:"bg-white rounded-lg p-4 shadow-sm print:bg-white print:rounded-none print:p-1.5 print:border print:border-gray-300",children:[t.jsx("p",{className:"text-gray-500 text-xs font-bold uppercase tracking-wide print:text-gray-600 print:text-xs print:tracking-normal",children:"Stream/Group"}),t.jsx("p",{className:"font-bold text-blue-600 text-lg mt-1 print:text-gray-900 print:text-sm print:mt-0",children:e.student.stream||"General"})]}),t.jsxs("div",{className:"bg-white rounded-lg p-4 shadow-sm print:bg-white print:rounded-none print:p-1.5 print:border print:border-gray-300",children:[t.jsx("p",{className:"text-gray-500 text-xs font-bold uppercase tracking-wide print:text-gray-600 print:text-xs print:tracking-normal",children:"Father's Name"}),t.jsx("p",{className:"font-bold text-gray-900 text-lg mt-1 print:text-sm print:mt-0",children:e.student.father_name||"N/A"})]}),t.jsxs("div",{className:"bg-white rounded-lg p-4 shadow-sm print:bg-white print:rounded-none print:p-1.5 print:border print:border-gray-300",children:[t.jsx("p",{className:"text-gray-500 text-xs font-bold uppercase tracking-wide print:text-gray-600 print:text-xs print:tracking-normal",children:"Exam Type"}),t.jsx("p",{className:"font-bold text-gray-900 text-lg mt-1 print:text-sm print:mt-0",children:a[i.exam_type]})]})]})})]})}),t.jsxs("div",{className:"p-10 print:px-6 print:py-3",children:[t.jsx("h3",{className:"text-lg font-bold text-gray-900 mb-6 print:text-base print:mb-2",children:"Academic Performance"}),t.jsx("div",{className:"overflow-hidden rounded-xl border border-gray-200 shadow-md print:rounded-none print:border-gray-300 print:shadow-none",children:t.jsxs("table",{className:"w-full border-collapse print:text-xs",children:[t.jsx("thead",{children:t.jsxs("tr",{className:"bg-gradient-to-r from-slate-800 to-slate-700 border-b border-slate-600 print:bg-gray-800 print:text-white",children:[t.jsx("th",{className:"text-left py-4 px-6 font-bold text-white text-sm tracking-wide print:py-2 print:px-3 print:text-xs print:font-bold",children:"Subject"}),t.jsx("th",{className:"text-center py-4 px-6 font-bold text-white text-sm tracking-wide print:py-2 print:px-3 print:text-xs print:font-bold",children:"Obtained"}),t.jsx("th",{className:"text-center py-4 px-6 font-bold text-white text-sm tracking-wide print:py-2 print:px-3 print:text-xs print:font-bold",children:"Total"}),t.jsx("th",{className:"text-center py-4 px-6 font-bold text-white text-sm tracking-wide print:py-2 print:px-3 print:text-xs print:font-bold",children:"%"}),t.jsx("th",{className:"text-center py-4 px-6 font-bold text-white text-sm tracking-wide print:py-2 print:px-3 print:text-xs print:font-bold",children:"Grade"}),t.jsx("th",{className:"text-center py-4 px-6 font-bold text-white text-sm tracking-wide print:py-2 print:px-3 print:text-xs print:font-bold",children:"GPA"})]})}),t.jsxs("tbody",{children:[e.results.map((n,l)=>t.jsxs("tr",{className:`border-b border-gray-200 transition-colors ${u(n.percentage)} hover:bg-opacity-75 print:bg-white print:border-gray-300`,children:[t.jsx("td",{className:"py-4 px-6 text-gray-900 font-semibold print:py-2 print:px-3 print:font-normal print:text-xs",children:n.subject_name}),t.jsx("td",{className:"py-4 px-6 text-center text-gray-900 font-medium print:py-2 print:px-3 print:text-xs",children:n.obtained_marks}),t.jsx("td",{className:"py-4 px-6 text-center text-gray-900 font-medium print:py-2 print:px-3 print:text-xs",children:n.total_marks}),t.jsxs("td",{className:`py-4 px-6 text-center font-bold text-base print:py-2 print:px-3 print:font-normal print:text-xs print:text-gray-900 ${o(n.percentage)}`,children:[n.percentage.toFixed(1),"%"]}),t.jsx("td",{className:`py-4 px-6 text-center font-black text-lg print:py-2 print:px-3 print:font-bold print:text-xs print:text-gray-900 ${o(n.percentage)}`,children:n.grade}),t.jsx("td",{className:"py-4 px-6 text-center text-gray-900 font-bold print:py-2 print:px-3 print:font-normal print:text-xs",children:Number(n.gpa_point).toFixed(2)})]},l)),t.jsxs("tr",{className:"bg-gradient-to-r from-indigo-100 to-blue-100 border-t-2 border-indigo-300 font-black print:bg-gray-100 print:border-gray-400",children:[t.jsx("td",{className:"py-5 px-6 text-gray-900 print:py-2 print:px-3 print:text-xs print:font-bold",children:"TOTAL"}),t.jsx("td",{className:"py-5 px-6 text-center text-gray-900 print:py-2 print:px-3 print:text-xs print:font-bold",children:e.summary.total_obtained}),t.jsx("td",{className:"py-5 px-6 text-center text-gray-900 print:py-2 print:px-3 print:text-xs print:font-bold",children:e.summary.total_possible}),t.jsxs("td",{className:`py-5 px-6 text-center text-lg print:py-2 print:px-3 print:text-xs print:font-bold print:text-gray-900 ${o(e.summary.overall_percentage)}`,children:[e.summary.overall_percentage.toFixed(1),"%"]}),t.jsx("td",{className:`py-5 px-6 text-center text-2xl print:py-2 print:px-3 print:text-xs print:font-bold print:text-gray-900 ${o(e.summary.overall_percentage)}`,children:e.summary.overall_grade}),t.jsx("td",{className:"py-5 px-6 text-center text-gray-900 print:py-2 print:px-3 print:text-xs print:font-bold",children:Number(e.summary.average_gpa).toFixed(2)})]})]})]})})]}),e.results.some(n=>n.class_teacher_remarks||n.principal_remarks)&&t.jsxs("div",{className:"px-10 py-8 border-b-2 border-gray-100 bg-blue-50 print:bg-white print:border-gray-300 print:py-4",children:[t.jsx("h3",{className:"text-lg font-bold text-gray-900 mb-4 print:text-base print:mb-3",children:"Teacher Remarks"}),t.jsx("div",{className:"space-y-4 print:space-y-2",children:e.results.map((n,l)=>(n.class_teacher_remarks||n.principal_remarks)&&t.jsxs("div",{className:"bg-white rounded-lg p-4 border border-gray-200 print:bg-white print:rounded-none print:p-2 print:border-gray-300 print:mb-2",children:[t.jsx("p",{className:"font-semibold text-gray-900 mb-2 print:text-sm print:mb-1",children:n.subject_name}),n.class_teacher_remarks&&t.jsxs("p",{className:"text-sm text-gray-700 mb-2 print:text-xs print:mb-1",children:[t.jsx("span",{className:"font-semibold text-blue-600 print:text-gray-900",children:"Class Teacher: "}),t.jsx("em",{children:n.class_teacher_remarks})]}),n.principal_remarks&&t.jsxs("p",{className:"text-sm text-gray-700 print:text-xs",children:[t.jsx("span",{className:"font-semibold text-indigo-600 print:text-gray-900",children:"Principal: "}),t.jsx("em",{children:n.principal_remarks})]})]},l))})]}),t.jsx("div",{className:"flex justify-center py-10 border-b-2 border-gray-100 bg-gray-50 print:py-3 print:border-gray-300 print:bg-white",children:t.jsx("div",{className:`${y(e.summary.pass_fail)} ${f(e.summary.pass_fail)} px-12 py-4 rounded-full font-black text-2xl shadow-lg transform hover:scale-105 transition-transform print:px-6 print:py-2 print:rounded-none print:text-sm print:shadow-none print:border print:border-gray-400`,children:e.summary.pass_fail==="PASS"?"✓ PASS":"✗ FAIL"})}),t.jsxs("div",{className:"px-10 py-8 bg-gradient-to-r from-gray-50 to-gray-100 print:bg-white print:px-6 print:py-4",children:[t.jsx("p",{className:"text-xs font-bold text-gray-500 uppercase tracking-widest mb-6 print:mb-3 print:text-gray-700",children:"Official Signatures"}),t.jsxs("div",{className:"grid grid-cols-2 gap-12 text-center print:gap-4",children:[t.jsxs("div",{className:"space-y-4 print:space-y-2",children:[t.jsx("div",{className:"h-16 border-b-2 border-gray-800 mx-auto w-4/5 print:h-8 print:border-b print:border-gray-700"}),t.jsx("p",{className:"text-sm font-bold text-gray-900 print:text-xs",children:"Class Teacher"}),t.jsx("p",{className:"text-xs text-gray-500 print:text-gray-600",children:"Signature & Date"})]}),t.jsxs("div",{className:"space-y-4 print:space-y-2",children:[t.jsx("div",{className:"h-16 border-b-2 border-gray-800 mx-auto w-4/5 print:h-8 print:border-b print:border-gray-700"}),t.jsx("p",{className:"text-sm font-bold text-gray-900 print:text-xs",children:"Principal"}),t.jsx("p",{className:"text-xs text-gray-500 print:text-gray-600",children:"Signature & Date"})]})]})]})]},e.student.id))})})]})}export{k as default};
