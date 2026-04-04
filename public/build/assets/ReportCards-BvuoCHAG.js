import{j as e,H as m}from"./app-BJob22Ar.js";import{F as j}from"./PrinterIcon-BrSLThqo.js";/* empty css            */const d=[{range:"91–100",grade:"A+"},{range:"81–90",grade:"A"},{range:"71–80",grade:"B+"},{range:"61–70",grade:"B"},{range:"51–60",grade:"C+"},{range:"41–50",grade:"C"},{range:"33–40",grade:"D"},{range:"0–32",grade:"F"}];function y({reportData:i,filters:r,examTypes:n,terms:a,classes:o}){const p=()=>window.print(),x=r.class_id?(()=>{const s=o.find(l=>l.id===Number(r.class_id));return s?`${s.class}${s.section?` — ${s.section}`:""}`:"All Classes"})():"All Classes";return e.jsxs(e.Fragment,{children:[e.jsx(m,{title:"Report Cards",children:e.jsx("style",{children:`
                    * { box-sizing: border-box; }

                    @media screen {
                        body { background: #e5e7eb; margin: 0; }
                        .page-wrapper { max-width: 900px; margin: 0 auto; padding: 24px 16px; }
                        .report-card { background: #fff; margin-bottom: 40px; border: 1px solid #ccc; }
                    }

                    @media print {
                        html, body { margin: 0; padding: 0; background: white !important; }
                        .no-print { display: none !important; }
                        .page-wrapper { padding: 0; margin: 0; }
                        .report-card {
                            page-break-after: always;
                            page-break-inside: avoid;
                            margin: 0 !important;
                            border: none !important;
                            box-shadow: none !important;
                        }
                        .report-card:last-child { page-break-after: avoid; }
                    }

                    @page { size: A4 portrait; margin: 8mm; }

                    /* Card internals */
                    .rc-header { border-bottom: 2px solid #1e3a5f; padding: 10px 16px 8px; }
                    .rc-school-name { font-size: 20px; font-weight: 900; text-transform: uppercase; color: #1e3a5f; letter-spacing: 0.5px; text-align: center; }
                    .rc-affil { font-size: 10px; text-align: center; color: #333; margin-top: 2px; }
                    .rc-contact { font-size: 9px; text-align: center; color: #555; margin-top: 1px; }
                    .rc-report-title { text-align: center; margin-top: 6px; }
                    .rc-report-title p { font-size: 13px; font-weight: 700; color: #1e3a5f; }
                    .rc-report-title small { font-size: 11px; color: #333; }

                    /* Student info */
                    .rc-student-info { display: grid; grid-template-columns: 1fr 1fr; gap: 0; border-bottom: 1px solid #555; font-size: 11px; }
                    .rc-info-left, .rc-info-right { padding: 8px 14px; }
                    .rc-info-left { border-right: 1px solid #555; }
                    .rc-info-row { display: flex; gap: 4px; margin-bottom: 4px; }
                    .rc-info-label { font-weight: 600; min-width: 110px; color: #111; }
                    .rc-info-value { color: #222; }

                    /* Marks table */
                    .rc-table { width: 100%; border-collapse: collapse; font-size: 11px; }
                    .rc-table th, .rc-table td { border: 1px solid #555; padding: 4px 8px; text-align: center; }
                    .rc-table th { background: #f0f0f0; font-weight: 700; color: #111; }
                    .rc-table td.subject-col { text-align: left; font-weight: 600; }
                    .rc-table tr.total-row td { background: #e8eaf6; font-weight: 700; }
                    .rc-section-title { font-size: 11px; font-weight: 700; background: #d0d8e8; padding: 4px 8px; border: 1px solid #555; border-bottom: none; }

                    /* Co-scholastic */
                    .rc-coscholastic table { width: 100%; border-collapse: collapse; font-size: 11px; }
                    .rc-coscholastic th, .rc-coscholastic td { border: 1px solid #555; padding: 4px 8px; }
                    .rc-coscholastic th { background: #f0f0f0; font-weight: 700; text-align: center; }

                    /* Footer */
                    .rc-footer { border-top: 1px solid #555; padding: 8px 14px 4px; font-size: 10px; }
                    .rc-signatures { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 0; margin-bottom: 8px; }
                    .rc-sig-box { text-align: center; padding: 4px; }
                    .rc-sig-line { border-top: 1px solid #333; margin: 20px 10px 4px; }
                    .rc-grade-scale { width: 100%; border-collapse: collapse; font-size: 9.5px; margin-top: 6px; }
                    .rc-grade-scale th, .rc-grade-scale td { border: 1px solid #777; padding: 2px 6px; text-align: center; }
                    .rc-grade-scale th { background: #f0f0f0; font-weight: 700; }

                    /* Pass/Fail strip */
                    .rc-result-strip { background: #1e3a5f; color: white; text-align: center; font-size: 13px; font-weight: 700; padding: 6px; letter-spacing: 1px; }
                    .rc-result-strip.fail { background: #b91c1c; }
                `})}),e.jsxs("div",{className:"no-print",style:{background:"#1e293b",color:"#fff",padding:"12px 24px",display:"flex",alignItems:"center",justifyContent:"space-between",position:"sticky",top:0,zIndex:50},children:[e.jsxs("div",{children:[e.jsx("p",{style:{fontWeight:700,fontSize:15},children:"KORT School — Report Cards"}),e.jsxs("p",{style:{fontSize:11,color:"#94a3b8",marginTop:2},children:[n[r.exam_type],"  •  ",a[r.term],"  •  ",r.academic_year,"  •  ",x,"  ",e.jsxs("span",{style:{background:"#334155",padding:"2px 10px",borderRadius:12,fontSize:10},children:[i.length," student",i.length!==1?"s":""]})]})]}),e.jsxs("button",{onClick:p,style:{background:"#2563eb",color:"#fff",border:"none",borderRadius:8,padding:"8px 20px",fontWeight:700,cursor:"pointer",display:"flex",alignItems:"center",gap:6,fontSize:13},children:[e.jsx(j,{style:{width:18,height:18}})," Print / Save PDF"]})]}),e.jsx("div",{className:"page-wrapper",children:i.length===0?e.jsxs("div",{style:{background:"#fff",borderRadius:12,padding:60,textAlign:"center",color:"#6b7280"},children:[e.jsx("p",{style:{fontSize:18,fontWeight:600},children:"No results found"}),e.jsx("p",{style:{fontSize:13,marginTop:8},children:"Select filters and a student, then click Preview Result."})]}):i.map(s=>{const l=s.summary.total_obtained,h=s.summary.total_possible,g=s.summary.overall_percentage,c=s.summary.pass_fail==="PASS";return e.jsxs("div",{className:"report-card",children:[e.jsxs("div",{className:"rc-header",children:[e.jsxs("div",{style:{display:"flex",alignItems:"center",gap:12},children:[e.jsx("div",{style:{width:64,height:64,borderRadius:"50%",border:"2px solid #1e3a5f",display:"flex",alignItems:"center",justifyContent:"center",flexShrink:0,background:"#1e3a5f"},children:e.jsx("span",{style:{color:"#fff",fontWeight:900,fontSize:26},children:"K"})}),e.jsxs("div",{style:{flex:1},children:[e.jsx("div",{className:"rc-school-name",children:"KORT SCHOOL"}),e.jsx("div",{className:"rc-affil",children:"Affiliated To: UK Board of Education  |  Affiliation No: KORT-2024-001"}),e.jsx("div",{className:"rc-contact",children:"Ph: +44 (0) 000 000 0000    Email: info@kort.org.uk    Visit us: www.kort.org.uk"}),e.jsxs("div",{className:"rc-report-title",style:{marginTop:8},children:[e.jsx("p",{children:"Academic Report"}),e.jsxs("small",{children:["Academic Session : ",r.academic_year,"   |   ",n[r.exam_type]," — ",a[r.term]]})]})]}),e.jsx("div",{style:{flexShrink:0,width:72},children:s.student.photo?e.jsx("img",{src:`/storage/${s.student.photo}`,alt:"",style:{width:72,height:88,objectFit:"cover",border:"1px solid #555"}}):e.jsx("div",{style:{width:72,height:88,background:"#e5e7eb",border:"1px solid #555",display:"flex",alignItems:"center",justifyContent:"center",fontSize:11,color:"#9ca3af"},children:"Photo"})})]}),e.jsxs("div",{style:{textAlign:"center",marginTop:6,fontSize:12,fontWeight:700,color:"#1e3a5f"},children:["Class : ",s.student.class?`${s.student.class.class}${s.student.class.section?` — ${s.student.class.section}`:""}`:"—",s.student.stream?` &nbsp;|&nbsp; Stream: ${s.student.stream}`:""]})]}),e.jsxs("div",{className:"rc-student-info",style:{borderTop:"1px solid #555"},children:[e.jsxs("div",{className:"rc-info-left",children:[e.jsxs("div",{className:"rc-info-row",children:[e.jsx("span",{className:"rc-info-label",children:"Name of Student"}),e.jsx("span",{children:": "}),e.jsx("span",{className:"rc-info-value",style:{fontWeight:700},children:s.student.full_name})]}),e.jsxs("div",{className:"rc-info-row",children:[e.jsx("span",{className:"rc-info-label",children:"Father's Name"}),e.jsx("span",{children:": "}),e.jsx("span",{className:"rc-info-value",children:s.student.father_name||"—"})]}),e.jsxs("div",{className:"rc-info-row",children:[e.jsx("span",{className:"rc-info-label",children:"Stream / Group"}),e.jsx("span",{children:": "}),e.jsx("span",{className:"rc-info-value",children:s.student.stream||"General"})]})]}),e.jsxs("div",{className:"rc-info-right",children:[e.jsxs("div",{className:"rc-info-row",children:[e.jsx("span",{className:"rc-info-label",children:"Admission No."}),e.jsx("span",{children:": "}),e.jsx("span",{className:"rc-info-value",style:{fontWeight:700},children:s.student.admission_no})]}),e.jsxs("div",{className:"rc-info-row",children:[e.jsx("span",{className:"rc-info-label",children:"Exam Type"}),e.jsx("span",{children:": "}),e.jsx("span",{className:"rc-info-value",children:n[r.exam_type]})]}),e.jsxs("div",{className:"rc-info-row",children:[e.jsx("span",{className:"rc-info-label",children:"Term"}),e.jsx("span",{children:": "}),e.jsx("span",{className:"rc-info-value",children:a[r.term]})]})]})]}),e.jsxs("div",{style:{padding:"0 14px 10px"},children:[e.jsx("div",{className:"rc-section-title",style:{marginTop:10},children:"Scholastic Areas"}),e.jsxs("table",{className:"rc-table",children:[e.jsxs("thead",{children:[e.jsxs("tr",{children:[e.jsx("th",{rowSpan:2,className:"subject-col",style:{textAlign:"left",width:"30%"},children:"Subject"}),e.jsx("th",{colSpan:2,children:a[r.term]}),e.jsx("th",{colSpan:2,children:"Overall"})]}),e.jsxs("tr",{children:[e.jsx("th",{children:"Obtained"}),e.jsx("th",{children:"Total"}),e.jsx("th",{children:"Grand Total"}),e.jsx("th",{children:"Grade"})]})]}),e.jsxs("tbody",{children:[s.results.map((t,f)=>e.jsxs("tr",{children:[e.jsx("td",{className:"subject-col",children:t.subject_name}),e.jsx("td",{children:t.obtained_marks}),e.jsx("td",{children:t.total_marks}),e.jsx("td",{children:t.obtained_marks}),e.jsx("td",{style:{fontWeight:700},children:t.grade})]},f)),e.jsxs("tr",{className:"total-row",children:[e.jsx("td",{style:{textAlign:"left"},children:"Attendance: —"}),e.jsxs("td",{colSpan:1,style:{fontWeight:700},children:["Total Marks: ",l," / ",h]}),e.jsx("td",{}),e.jsxs("td",{style:{fontWeight:700},children:["Percentage: ",g.toFixed(1),"%"]}),e.jsx("td",{style:{fontWeight:700},children:s.summary.overall_grade})]})]})]})]}),e.jsxs("div",{className:"rc-coscholastic",style:{padding:"0 14px 10px"},children:[e.jsx("div",{className:"rc-section-title",children:"CO-SCHOLASTIC  (3 POINT GRADING SCALE: A, B, C)"}),e.jsxs("table",{children:[e.jsx("thead",{children:e.jsxs("tr",{children:[e.jsx("th",{style:{textAlign:"left",width:"50%"},children:"Activity"}),e.jsx("th",{children:"Term-I"}),e.jsx("th",{children:"Term-II"})]})}),e.jsx("tbody",{children:["Uniform & Discipline","Activities & Sports","Digital Literacy","Written Skills","Speaking Skills"].map(t=>e.jsxs("tr",{children:[e.jsx("td",{children:t}),e.jsx("td",{style:{textAlign:"center"},children:"—"}),e.jsx("td",{style:{textAlign:"center"},children:"—"})]},t))})]})]}),e.jsxs("div",{className:`rc-result-strip${c?"":" fail"}`,children:["RESULT : ",c?"PASS":"FAIL","   |   Overall Grade : ",s.summary.overall_grade,"   |   GPA : ",Number(s.summary.average_gpa).toFixed(2)]}),e.jsxs("div",{className:"rc-footer",children:[e.jsx("div",{className:"rc-signatures",children:["Sign. of Class Teacher","Sign. of Principal","Sign. of Manager"].map(t=>e.jsxs("div",{className:"rc-sig-box",children:[e.jsx("div",{className:"rc-sig-line"}),e.jsx("p",{style:{fontWeight:600,fontSize:10},children:t})]},t))}),e.jsxs("div",{style:{borderTop:"1px solid #ccc",paddingTop:6},children:[e.jsx("p",{style:{fontWeight:700,marginBottom:4,fontSize:10},children:"Instructions & Grading Scale for Scholastic Areas:"}),e.jsxs("table",{className:"rc-grade-scale",children:[e.jsx("thead",{children:e.jsxs("tr",{children:[e.jsx("th",{children:"Marks Range (%)"}),d.map(t=>e.jsx("th",{children:t.range},t.grade))]})}),e.jsx("tbody",{children:e.jsxs("tr",{children:[e.jsx("td",{style:{fontWeight:700,textAlign:"left",paddingLeft:8},children:"Grade"}),d.map(t=>e.jsx("td",{style:{fontWeight:700},children:t.grade},t.grade))]})})]}),e.jsx("p",{style:{marginTop:5,fontSize:9,color:"#555"},children:"Grades are awarded on an 8-point grading scale as shown above. Students scoring below 33% are considered FAIL."})]})]})]},s.student.id)})})]})}export{y as default};
