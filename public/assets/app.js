// Lightweight UI interactions + lottie load
document.addEventListener('DOMContentLoaded', function(){
  try{
    lottie.loadAnimation({container:document.getElementById('lottie-left'), renderer:'svg', loop:true, autoplay:true, path:'https://assets6.lottiefiles.com/packages/lf20_jcikwtux.json'});
  }catch(e){console.warn(e)}

  // role toggle
  document.querySelectorAll('.role-btn').forEach(b=>b.addEventListener('click', ()=>{document.querySelectorAll('.role-btn').forEach(x=>x.classList.remove('active'));b.classList.add('active');document.getElementById('roleInput') && (document.getElementById('roleInput').value = b.dataset.role);}));

  // password toggle
  const pToggle = document.querySelector('.pass-toggle');
  if (pToggle){
    pToggle.addEventListener('click', ()=>{
      const p = document.getElementById('password');
      if (p.type==='password'){ p.type='text'; pToggle.innerText='Hide'; } else { p.type='password'; pToggle.innerText='Show'; }
    });
  }

  // run scoring (admin)
  const runBtn = document.getElementById('runScoringBtn');
  if (runBtn){
    runBtn.addEventListener('click', ()=>{
      runBtn.disabled = true; runBtn.innerText='Running...';
      fetch('/student_appraisal/php/calculate_marks.php').then(r=>r.text()).then(txt=>{ alert('Scoring finished'); runBtn.disabled=false; runBtn.innerText='Run Scoring'; }).catch(e=>{alert('Error'); runBtn.disabled=false; runBtn.innerText='Run Scoring';});
    });
  }

  // upload certificate form ajax
  const certForm = document.getElementById('certForm');
  if (certForm){
    certForm.addEventListener('submit', function(e){
      e.preventDefault();
      const fd = new FormData(certForm);
      fetch(certForm.action, {method:'POST', body:fd}).then(r=>r.json()).then(res=>{
        if (res.success) document.getElementById('ocrResult').innerText = 'Uploaded — OCR: ' + (res.ocr_output || '').join('\n');
        else document.getElementById('ocrResult').innerText = 'Upload failed';
      }).catch(err=>{document.getElementById('ocrResult').innerText='Error';});
    });
  }
});
