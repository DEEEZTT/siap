document.addEventListener('click',event=>{const target=event.target.closest('[data-confirm]');if(target&&!window.confirm(target.dataset.confirm))event.preventDefault()});document.addEventListener('submit',event=>{const form=event.target.closest('[data-confirm]');if(form&&!window.confirm(form.dataset.confirm))event.preventDefault()});

let outgoingDocumentDialog;
let invitationPreviewDialog;
document.addEventListener('click',event=>{
    const button=event.target.closest('[data-invitation-preview-open]');
    if(!button)return;
    event.preventDefault();
    const form=button.closest('.outgoing-letter-form-undangan');
    const paper=form?.parentElement?.querySelector('.letter-live-preview .letter-paper');
    if(!paper)return;
    if(!invitationPreviewDialog){
        invitationPreviewDialog=document.createElement('dialog');
        invitationPreviewDialog.className='invitation-preview-dialog';
        invitationPreviewDialog.setAttribute('aria-labelledby','invitation-preview-title');
        invitationPreviewDialog.innerHTML='<header class="invitation-preview-header"><div><span class="eyebrow blue">PRATINJAU SURAT</span><h2 id="invitation-preview-title">Surat undangan</h2><p>Pratinjau mengikuti data yang sedang diisi.</p></div><button class="button button-light" type="button" data-invitation-preview-close>Tutup</button></header><div class="invitation-preview-scroll"><div class="invitation-preview-paper-host"></div></div>';
        document.body.append(invitationPreviewDialog);
        invitationPreviewDialog.querySelector('[data-invitation-preview-close]').addEventListener('click',()=>invitationPreviewDialog.close());
        invitationPreviewDialog.addEventListener('click',click=>{if(click.target===invitationPreviewDialog)invitationPreviewDialog.close()});
    }
    const clone=paper.cloneNode(true);
    clone.removeAttribute('style');
    invitationPreviewDialog.querySelector('.invitation-preview-paper-host').replaceChildren(clone);
    invitationPreviewDialog.showModal();
},{capture:true});
document.addEventListener('click',event=>{
    const link=event.target.closest('[data-outgoing-document-preview]');
    if(!link||event.button!==0||event.ctrlKey||event.metaKey||event.shiftKey||event.altKey)return;
    event.preventDefault();
    event.stopImmediatePropagation();
    if(!outgoingDocumentDialog){
        outgoingDocumentDialog=document.createElement('dialog');
        outgoingDocumentDialog.className='outgoing-document-dialog';
        outgoingDocumentDialog.setAttribute('aria-label','Pratinjau dokumen surat keluar');
        outgoingDocumentDialog.innerHTML='<header class="outgoing-document-dialog-header"><strong>Pratinjau surat</strong><div class="outgoing-document-dialog-actions"><a class="button button-light" data-outgoing-document-download>Unduh PDF</a><button class="button button-light" type="button" data-outgoing-document-print>Cetak</button><button class="button button-light" type="button" data-outgoing-document-close aria-label="Tutup pratinjau">Tutup</button></div></header><iframe class="outgoing-document-frame" title="Pratinjau dokumen surat keluar"></iframe>';
        document.body.append(outgoingDocumentDialog);
        outgoingDocumentDialog.addEventListener('click',click=>{if(click.target===outgoingDocumentDialog)outgoingDocumentDialog.close()});
        outgoingDocumentDialog.querySelector('[data-outgoing-document-close]').addEventListener('click',()=>outgoingDocumentDialog.close());
        outgoingDocumentDialog.querySelector('[data-outgoing-document-print]').addEventListener('click',()=>{
            try{outgoingDocumentDialog.querySelector('iframe').contentWindow.print()}catch{window.alert('Pratinjau belum selesai dimuat. Coba lagi sebentar.')}
        });
        outgoingDocumentDialog.addEventListener('close',()=>{outgoingDocumentDialog.querySelector('iframe').src='about:blank'});
    }
    const previewUrl=new URL(link.href,window.location.href);
    previewUrl.searchParams.delete('download');
    outgoingDocumentDialog.querySelector('iframe').src=previewUrl.href;
    const downloadUrl=new URL(previewUrl.href);
    downloadUrl.searchParams.set('download','1');
    outgoingDocumentDialog.querySelector('[data-outgoing-document-download]').href=downloadUrl.href;
    if(!outgoingDocumentDialog.open)outgoingDocumentDialog.showModal();
},{capture:true});

// Confirm outgoing letters with a reviewable summary before submitting for verification.
document.addEventListener('submit',event=>{
    const form=event.target.closest('.outgoing-letter-form');
    const submitter=event.submitter;
    if(!form||submitter?.name!=='action'||submitter.value!=='submit')return;
    if(form.dataset.outgoingSubmitConfirmed==='true'){
        delete form.dataset.outgoingSubmitConfirmed;
        return;
    }
    event.preventDefault();
    let dialog=document.querySelector('.outgoing-submit-confirmation');
    if(!dialog){
        dialog=document.createElement('dialog');
        dialog.className='outgoing-submit-confirmation';
        dialog.setAttribute('aria-labelledby','outgoing-submit-title');
        dialog.innerHTML='<div class="outgoing-submit-dialog-content"><span class="eyebrow blue">PERIKSA KEMBALI</span><h2 id="outgoing-submit-title">Konfirmasi pengiriman surat</h2><p>Pastikan data dan lampiran surat sudah benar sebelum dikirim untuk verifikasi.</p><dl class="outgoing-submit-summary"></dl><div class="outgoing-submit-actions"><button class="button button-light" type="button" data-outgoing-confirm-cancel>Batal</button><button class="button" type="button" data-outgoing-confirm-submit>Konfirmasi dan Simpan</button></div></div>';
        document.body.append(dialog);
        dialog.addEventListener('click',click=>{if(click.target===dialog)dialog.close()});
        dialog.querySelector('[data-outgoing-confirm-cancel]').addEventListener('click',()=>dialog.close());
        dialog.querySelector('[data-outgoing-confirm-submit]').addEventListener('click',()=>{
            const pendingForm=dialog.pendingForm;
            const pendingSubmitter=dialog.pendingSubmitter;
            dialog.close();
            if(pendingForm?.isConnected){pendingForm.dataset.outgoingSubmitConfirmed='true';pendingForm.requestSubmit(pendingSubmitter)}
        });
    }
    const value=name=>form.elements.namedItem(name)?.value?.trim()||'';
    const type=form.querySelector('[name="type"]')?.value||form.className.match(/outgoing-letter-form-([\w-]+)/)?.[1]||'Surat keluar';
    const taskRecipients=[...form.querySelectorAll('[name^="details[recipients]["][name$="[name]"]')].map(input=>input.value.trim()).filter(Boolean);
    const recipient=value('recipient_name')||taskRecipients.join(', ')||'—';
    const subject=value('subject')||({undangan:'Undangan',perintah_tugas:'Perintah Tugas',rekomendasi_mutasi:'Rekomendasi Mutasi / Pindah Siswa'}[type]||'—');
    const attachmentValues=[...form.querySelectorAll('[name="details[attachments]"]')].map(field=>field.value.trim()).filter(text=>text&&text!=='—'&&text!=='-');
    const uploadedFiles=[...form.querySelectorAll('input[type="file"]')].flatMap(input=>[...input.files].map(file=>file.name));
    const rows=[['Jenis surat',type.replaceAll('_',' ')],['Tujuan / penerima',recipient],['Perihal',subject]];
    if(value('event_date'))rows.push(['Tanggal kegiatan',value('event_date')]);
    if(value('event_time'))rows.push(['Waktu kegiatan',value('event_time')]);
    if(value('event_place'))rows.push(['Tempat kegiatan',value('event_place')]);
    if(value('details[basis]'))rows.push(['Dasar penugasan',value('details[basis]')]);
    rows.push(['Lampiran',uploadedFiles.concat(attachmentValues).join(', ')||'Tidak ada']);
    const summary=dialog.querySelector('.outgoing-submit-summary');
    summary.replaceChildren(...rows.flatMap(([label,text])=>{const dt=document.createElement('dt'),dd=document.createElement('dd');dt.textContent=label;dd.textContent=text;return[dt,dd]}));
    dialog.pendingForm=form;
    dialog.pendingSubmitter=submitter;
    if(!dialog.open)dialog.showModal();
},{capture:true});

// Open create pages in an overlay and submit them without navigating away.
let createModal;
function openCreateModal(modal){
    document.documentElement.classList.add('modal-scroll-locked');
    document.body.classList.add('modal-scroll-locked');
    modal.showModal();
}
function ensureCreateModal(){
    if(createModal)return createModal;
    createModal=document.createElement('dialog');
    createModal.className='create-modal';
    createModal.setAttribute('aria-label','Tambah data');
    createModal.innerHTML='<button class="create-modal-close" type="button" aria-label="Tutup">×</button><div class="create-modal-content" aria-live="polite"></div>';
    document.body.append(createModal);
    createModal.addEventListener('close',()=>{
        document.documentElement.classList.remove('modal-scroll-locked');
        document.body.classList.remove('modal-scroll-locked');
    });
    createModal.querySelector('.create-modal-close').addEventListener('click',()=>createModal.close());
    const hasActiveInputForm=()=>createModal.dataset.modalKind==='create-form'||createModal.dataset.modalKind==='incoming-edit'||createModal.dataset.modalKind==='outgoing-edit'||createModal.dataset.modalKind==='student-edit'||createModal.dataset.modalKind==='company-edit'||!!createModal.querySelector('.modal-disposition-form,.outgoing-review-form,.incoming-letter-form,.outgoing-letter-form,.student-page-form,.company-page-form,.certificate-signatory-form');
    createModal.addEventListener('click',event=>{
        if(event.target!==createModal)return;
        if(hasActiveInputForm())event.preventDefault();
        else createModal.close();
    });
    createModal.addEventListener('cancel',event=>{
        if(hasActiveInputForm())event.preventDefault();
    });
    return createModal;
}
function pageContent(html){return new DOMParser().parseFromString(html,'text/html')}
function initializeCompanyForm(root){
    const form=root.querySelector('.company-page-form');
    if(!form||form.dataset.companyFormInitialized)return;
    form.dataset.companyFormInitialized='true';
    const partnershipType=form.elements.namedItem('partnership_type'),uploadField=form.querySelector('[data-mou-upload]'),uploadInput=form.elements.namedItem('mou_document');
    if(!partnershipType||!uploadField||!uploadInput)return;
    const agreementFields=[...form.querySelectorAll('[data-agreement-field]')];
    const update=()=>{
        const isMou=partnershipType.value==='mou';
        agreementFields.forEach(field=>{field.hidden=!isMou;field.querySelectorAll('input,select,textarea').forEach(input=>{input.disabled=!isMou})});
        uploadField.hidden=!isMou;uploadInput.disabled=!isMou;
        if(!isMou)uploadInput.value='';
    };
    partnershipType.addEventListener('change',update);
    update();
}
function layoutDispositionForm(root){
    const form=root.querySelector('.modal-disposition-form');
    if(!form)return;
    form.style.setProperty('display','grid','important');
    form.style.setProperty('grid-template-columns','minmax(0,1fr) minmax(0,1fr)','important');
    form.style.setProperty('gap','10px 14px','important');
    const fields=[...form.querySelectorAll(':scope > .field')];
    fields.forEach((field,index)=>{
        field.style.setProperty('min-width','0');
        field.style.setProperty('margin-top','0','important');
        field.style.setProperty('grid-column',index===0||index===2?'1':'2','important');
    });
    const recipients=form.querySelector('.checkbox-list');
    if(recipients){recipients.style.setProperty('display','grid','important');recipients.style.setProperty('grid-template-columns','repeat(2,minmax(0,1fr))','important')}
    const button=form.querySelector(':scope > button[type="submit"]');
    if(button){button.style.setProperty('grid-column','1 / -1','important');button.style.setProperty('justify-self','end')}
}
function updateOutgoingPreview(root=document){
    const form=root.querySelector('.outgoing-letter-form');
    if(!form)return;
    const preview=root.querySelector('.letter-live-preview');
    if(!preview)return;
    const fields={invitation_context:'details[invitation_context]',grade_class:'details[grade_class]',attachments:'details[attachments]',event_date:'event_date',event_time:'event_time',event_place:'event_place',recipient_name:'recipient_name',rank:'details[rank]',employee_number:'details[employee_number]',position:'details[position]',basis:'details[basis]',assignment:'details[assignment]',nisn:'details[nisn]',class_info:'details[class_info]',origin_school:'details[origin_school]',destination_school:'details[destination_school]',origin_school_address:'details[origin_school_address]',birth_place:'details[birth_place]',birth_date:'details[birth_date]'};
    Object.assign(fields,{father_name:'details[father_name]',father_occupation:'details[father_occupation]',mother_name:'details[mother_name]',mother_occupation:'details[mother_occupation]',reason:'details[reason]'});
    const values={};
    Object.entries(fields).forEach(([key,name])=>{const control=form.elements.namedItem(name);values[key]=control?.value?.trim()||''});
    Object.entries(values).forEach(([key,value])=>preview.querySelectorAll(`[data-preview-field="${key}"]`).forEach(node=>{
        if(key==='event_date'||key==='birth_date'){
            if(!value){node.textContent='…';return}
            const date=new Date(`${value}T00:00:00`);
            node.textContent=Number.isNaN(date.getTime())?value:new Intl.DateTimeFormat('id-ID',{weekday:key==='event_date'?'long':undefined,day:'numeric',month:'long',year:'numeric'}).format(date);
        }else node.textContent=value||node.dataset.placeholder||(key==='attachments'?'-':'…');
    }));
    preview.querySelectorAll('.task-letter-basis [data-preview-field="basis"],.task-assignment-text').forEach(node=>{
        const wrappedText=document.createElement('span');
        wrappedText.className='task-preview-wrapped-text';
        wrappedText.textContent=node.textContent;
        node.replaceChildren(wrappedText);
    });
    [['father','father_name','father_occupation'],['mother','mother_name','mother_occupation']].forEach(([rowKey,nameKey,occupationKey])=>{
        const row=preview.querySelector(`[data-preview-row="${rowKey}"]`);
        if(row)row.hidden=!values[nameKey]&&!values[occupationKey];
    });
    const parentHeading=preview.querySelector('[data-preview-row="parent-label"]');
    if(parentHeading)parentHeading.hidden=!(values.father_name||values.father_occupation||values.mother_name||values.mother_occupation);
    const reasonRow=preview.querySelector('[data-preview-row="reason"]');
    if(reasonRow)reasonRow.hidden=!values.reason;
    const attachmentList=preview.querySelector('[data-preview-attachments]');
    if(attachmentList){
        const attachments=values.attachments.split(/\r?\n/).map(item=>item.trim()).filter(Boolean);
        attachmentList.replaceChildren(...attachments.map(item=>{const entry=document.createElement('li');entry.textContent=item;return entry}));
        const attachmentRow=preview.querySelector('[data-preview-row="attachments"]');
        if(attachmentRow)attachmentRow.hidden=!attachments.length;
    }
    const recipientPreview=preview.querySelector('[data-preview-recipients]');
    if(recipientPreview){
        const recipients=[{
            name:values.recipient_name,
            rank:values.rank,
            employee_number:values.employee_number,
            position:values.position
        },...[...form.querySelectorAll('[data-task-recipient-list] .task-recipient-input-row')].map(row=>Object.fromEntries(['name','rank','employee_number','position'].map(key=>[key,row.querySelector(`[name$="[${key}]"]`)?.value.trim()||''])))].filter(recipient=>recipient.name);
        const labels={name:'nama',rank:'pangkat/golongan',employee_number:'NIP/NUPTK',position:'jabatan'};
        if(!recipients.length)recipients.push({name:'[nama pegawai]',rank:'',employee_number:'',position:''});
        recipientPreview.replaceChildren();
        recipients.forEach((recipient,index)=>{
            Object.entries(labels).forEach(([key,label])=>{
                if(key!=='name'&&!recipient[key])return;
                const row=document.createElement('tr');row.className='task-recipient-row';
                const widths=['22%','3%','3%','20%','3%','49%'];
                if(key==='name'){
                    const recipientLabel=document.createElement('td');recipientLabel.setAttribute('width',widths[0]);recipientLabel.textContent=index===0?'Kepada':'';row.append(recipientLabel);
                    const recipientColon=document.createElement('td');recipientColon.setAttribute('width',widths[1]);recipientColon.textContent=index===0?':':'';row.append(recipientColon);
                    const number=document.createElement('td');number.setAttribute('width',widths[2]);number.textContent=`${index+1}.`;row.append(number);
                }else{widths.slice(0,3).forEach(width=>{const blank=document.createElement('td');blank.setAttribute('width',width);row.append(blank)})}
                const title=document.createElement('td');title.setAttribute('width',widths[3]);title.textContent=label;const colon=document.createElement('td');colon.setAttribute('width',widths[4]);colon.textContent=':';const value=document.createElement('td');value.setAttribute('width',widths[5]);value.textContent=recipient[key]||'…';row.append(title,colon,value);
                recipientPreview.append(row);
            });
        });
    }
    ['rank','employee_number','position'].forEach(key=>{const row=preview.querySelector(`[data-preview-row="${key}"]`);if(row)row.hidden=!values[key]});
    const participantPreview=preview.querySelector('[data-preview-participants]');
    if(participantPreview){
        const rows=[...form.querySelectorAll('[data-task-participant-list] .task-participant-input-row')].map(row=>({name:row.querySelector('[name$="[name]"]')?.value.trim(),group:row.querySelector('[name$="[class]"]')?.value.trim()})).filter(row=>row.name);
        participantPreview.replaceChildren();
        if(rows.length){
            participantPreview.hidden=false;
            const table=document.createElement('table');table.className='task-participant-table';table.setAttribute('width','100%');
            const widths=['7%','46%','47%'];
            const head=document.createElement('thead');const headingRow=document.createElement('tr');['No','Nama','Kelas'].forEach((label,index)=>{const cell=document.createElement('th');cell.setAttribute('width',widths[index]);cell.style.width=widths[index];cell.style.textAlign='left';cell.textContent=label;headingRow.append(cell)});head.append(headingRow);table.append(head);
            const body=document.createElement('tbody');rows.forEach((participant,index)=>{const row=document.createElement('tr');[`${index+1}.`,participant.name,participant.group||''].forEach((value,columnIndex)=>{const cell=document.createElement('td');cell.setAttribute('width',widths[columnIndex]);cell.style.width=widths[columnIndex];cell.style.textAlign='left';if(columnIndex===1)cell.style.overflowWrap='anywhere';cell.textContent=value;row.append(cell)});body.append(row)});table.append(body);participantPreview.append(table);
        }else participantPreview.hidden=true;
    }
    requestAnimationFrame(()=>fitLetterPreview(preview));
}
function fitLetterPreview(preview){const viewport=preview?.querySelector('.letter-preview-viewport'),paper=viewport?.querySelector('.letter-paper');if(!viewport||!paper)return;const scale=viewport.clientWidth/paper.offsetWidth;if(!scale)return;paper.style.transform=`scale(${scale})`;viewport.style.height=`${paper.scrollHeight*scale}px`}
function updateEventCertificatePreview(root=document){
    const form=root.querySelector('.event-certificate-form');
    if(!form)return;
    const preview=root.querySelector('.event-certificate-live-preview');
    if(!preview)return;
    const value=name=>form.elements.namedItem(name)?.value?.trim()||'';
    const set=(key,text,fallback)=>{const node=preview.querySelector(`[data-certificate-preview="${key}"]`);if(node)node.textContent=text||fallback};
    const category=value('category')||'penghargaan';
    const categoryLabel=form.elements.namedItem('category')?.selectedOptions?.[0]?.textContent?.trim()||'Lainnya';
    const certificate=preview.querySelector('.certificate');
    if(certificate){certificate.classList.remove('certificate-category-penghargaan','certificate-category-peserta','certificate-category-narasumber','certificate-category-lainnya');certificate.classList.add(`certificate-category-${category}`)}
    set('category',categoryLabel,'Penghargaan / prestasi');
    set('title',value('title'),({penghargaan:'Sertifikat Penghargaan',peserta:'Sertifikat Peserta Kegiatan',narasumber:'Sertifikat Narasumber',lainnya:'Sertifikat Kegiatan'})[category]||'Sertifikat Kegiatan');
    set('recipient',value('recipient_name'),'Nama penerima');
    set('description',value('description'),'Uraian sertifikat akan tampil di sini');
    set('signer-title',value('signer_title').toLocaleUpperCase('id-ID'),'JABATAN PENANDA TANGAN');
    set('signer-name',value('signer_name'),'Nama penanda tangan');
    const nip=value('signer_nip'),nipNode=preview.querySelector('[data-certificate-preview="signer-nip"]');
    if(nipNode){nipNode.textContent=nip?`NIP ${nip}`:'';nipNode.hidden=!nip}
    const issuedAt=value('issued_at'),dateNode=preview.querySelector('[data-certificate-preview="date"]');
    if(dateNode){const date=issuedAt?new Date(`${issuedAt}T00:00:00`):null;dateNode.textContent=date&&!Number.isNaN(date.getTime())?new Intl.DateTimeFormat('id-ID',{day:'numeric',month:'long',year:'numeric'}).format(date):'Tanggal terbit'}
    requestAnimationFrame(()=>fitEventCertificateTitles());
}
function copyPageTitle(page){const title=page.querySelector('.topbar strong');if(title){const current=document.querySelector('.topbar strong');if(current)current.textContent=title.textContent}document.title=page.title}
document.addEventListener('click',async event=>{
    const link=event.target.closest('a[href]');
    if(!link||event.button!==0||event.ctrlKey||event.metaKey||event.shiftKey||event.altKey)return;
    if(link.closest('.create-modal')&&link.closest('.form-actions')&&link.textContent.trim().toLowerCase()==='batal'){event.preventDefault();link.closest('.create-modal').close();return}
    const url=new URL(link.href,location.href);
    if(link.hasAttribute('data-pkl-certificate-batch-modal')){
        if(url.origin!==location.origin)return;
        event.preventDefault();
        const modal=ensureCreateModal(),content=modal.querySelector('.create-modal-content');
        modal.dataset.modalKind='pkl-certificate-batch';
        modal.setAttribute('aria-label','Semua sertifikat PKL terbit');
        content.innerHTML='<div class="empty-state">Memuat semua sertifikat terbit…</div>';
        content.scrollTop=0;modal.scrollTop=0;
        openCreateModal(modal);
        try{
            const response=await fetch(url,{headers:{'X-Requested-With':'XMLHttpRequest','Accept':'text/html'}});
            if(!response.ok)throw new Error('Daftar sertifikat tidak dapat dimuat.');
            const page=pageContent(await response.text()),preview=page.querySelector('.pkl-certificate-batch-preview');
            if(!preview)throw new Error('Pratinjau sertifikat tidak ditemukan.');
            content.innerHTML=preview.outerHTML;
        }catch(error){content.innerHTML=`<div class="alert alert-error">${error.message}</div>`}
        return;
    }
    if(link.hasAttribute('data-detail-modal')){
        if(url.origin!==location.origin)return;
        event.preventDefault();
        const outgoingDetail=link.hasAttribute('data-outgoing-detail-modal');
        const certificatePreview=link.hasAttribute('data-certificate-preview-modal');
        const pklCertificatePreview=link.hasAttribute('data-pkl-certificate-preview-modal');
        const modal=ensureCreateModal(),content=modal.querySelector('.create-modal-content');
        modal.setAttribute('aria-label',pklCertificatePreview?'Pratinjau sertifikat PKL':certificatePreview?'Pratinjau sertifikat':outgoingDetail?'Rincian surat keluar':'Rincian surat masuk');
        modal.dataset.modalKind=pklCertificatePreview?'pkl-certificate-preview':certificatePreview?'certificate-preview':outgoingDetail?'outgoing-detail':'incoming-detail';
        modal.dataset.previewUrl=url.href;
        content.innerHTML=(certificatePreview||pklCertificatePreview)?'<div class="empty-state">Memuat pratinjau sertifikat…</div>':'<div class="empty-state">Memuat rincian surat…</div>';
        content.scrollTop=0;modal.scrollTop=0;
        openCreateModal(modal);
        fetch(url,{headers:{'X-Requested-With':'XMLHttpRequest','Accept':'text/html'}})
            .then(response=>{if(!response.ok)throw new Error('Rincian surat tidak dapat dimuat.');return response.text()})
            .then(html=>{
                const page=pageContent(html),preview=page.querySelector('.modal-record-preview');
                if(!preview)throw new Error((certificatePreview||pklCertificatePreview)?'Pratinjau sertifikat tidak ditemukan.':'Isi rincian surat tidak ditemukan.');
                content.innerHTML=preview.outerHTML;
                layoutDispositionForm(content);
                if(certificatePreview)requestAnimationFrame(fitEventCertificateTitles);
            })
            .catch(error=>{content.innerHTML=`<div class="alert alert-error">${error.message}</div>`});
        return;
    }
    if(link.hasAttribute('data-student-edit-modal')){
        event.preventDefault();
        const modal=ensureCreateModal(),content=modal.querySelector('.create-modal-content');
        modal.dataset.modalKind='student-edit';
        modal.dataset.createUrl=url.href;
        modal.setAttribute('aria-label','Ubah data siswa PKL');
        content.innerHTML='<div class="empty-state">Memuat formulir siswa…</div>';
        content.scrollTop=0;modal.scrollTop=0;
        openCreateModal(modal);
        try{
            const response=await fetch(url,{headers:{'X-Requested-With':'XMLHttpRequest','Accept':'text/html'}});
            if(!response.ok)throw new Error('Form ubah siswa tidak dapat dimuat.');
            const page=pageContent(await response.text()),section=page.querySelector('.page-wrap');
            if(!section?.querySelector('.student-page-form'))throw new Error('Form ubah siswa tidak ditemukan.');
            content.innerHTML=section.innerHTML;
        }catch(error){content.innerHTML=`<div class="alert alert-error">${error.message}</div>`}
        return;
    }
    if(link.hasAttribute('data-company-edit-modal')){
        if(url.origin!==location.origin)return;
        event.preventDefault();
        const modal=ensureCreateModal(),content=modal.querySelector('.create-modal-content');
        modal.dataset.modalKind='company-edit';
        modal.dataset.createUrl=url.href;
        modal.setAttribute('aria-label','Ubah data mitra');
        content.innerHTML='<div class="empty-state">Memuat formulir mitra…</div>';
        content.scrollTop=0;modal.scrollTop=0;
        openCreateModal(modal);
        try{
            const response=await fetch(url,{headers:{'X-Requested-With':'XMLHttpRequest','Accept':'text/html'}});
            if(!response.ok)throw new Error('Form ubah mitra tidak dapat dimuat.');
            const page=pageContent(await response.text()),section=page.querySelector('.page-wrap');
            if(!section?.querySelector('.company-page-form'))throw new Error('Form ubah mitra tidak ditemukan.');
            content.innerHTML=section.innerHTML;
            initializeCompanyForm(content);
        }catch(error){content.innerHTML=`<div class="alert alert-error">${error.message}</div>`}
        return;
    }
    if(link.hasAttribute('data-incoming-edit-modal')){
        const modal=ensureCreateModal(),content=modal.querySelector('.create-modal-content');
        event.preventDefault();
        modal.dataset.modalKind='incoming-edit';
        content.innerHTML='<div class="empty-state">Memuat formulir ubah surat…</div>';
        try{
            const response=await fetch(url,{headers:{'X-Requested-With':'XMLHttpRequest','Accept':'text/html'}});
            if(!response.ok)throw new Error('Form ubah surat tidak dapat dimuat.');
            const page=pageContent(await response.text()),section=page.querySelector('.page-wrap');
            const form=section?.querySelector('.incoming-letter-form');
            if(!form)throw new Error('Form ubah surat tidak ditemukan.');
            content.innerHTML=section.innerHTML;
        }catch(error){content.innerHTML=`<div class="alert alert-error">${error.message}</div>`}
        return;
    }
    if(link.hasAttribute('data-outgoing-edit-modal')){
        if(url.origin!==location.origin)return;
        event.preventDefault();
        const modal=ensureCreateModal(),content=modal.querySelector('.create-modal-content');
        modal.dataset.modalKind='outgoing-edit';
        modal.setAttribute('aria-label','Ubah surat keluar');
        content.innerHTML='<div class="empty-state">Memuat formulir ubah surat…</div>';
        content.scrollTop=0;modal.scrollTop=0;
        try{
            const response=await fetch(url,{headers:{'X-Requested-With':'XMLHttpRequest','Accept':'text/html'}});
            if(!response.ok)throw new Error('Form ubah surat tidak dapat dimuat.');
            const page=pageContent(await response.text()),section=page.querySelector('.page-wrap');
            const form=section?.querySelector('.outgoing-letter-form');
            if(!form)throw new Error('Form ubah surat tidak ditemukan.');
            content.innerHTML=section.innerHTML;
            updateOutgoingPreview(content);
        }catch(error){content.innerHTML=`<div class="alert alert-error">${error.message}</div>`}
        return;
    }
    if(url.origin!==location.origin||!/(?:^|\/)create(?:\/|$)/.test(url.pathname))return;
    event.preventDefault();
    const modal=ensureCreateModal(),content=modal.querySelector('.create-modal-content');
    modal.dataset.modalKind='create-form';
    delete modal.dataset.previewUrl;
    content.innerHTML='<div class="empty-state">Memuat formulir…</div>';
    content.scrollTop=0;modal.scrollTop=0;
    openCreateModal(modal);
    try{
        const response=await fetch(url,{headers:{'X-Requested-With':'XMLHttpRequest','Accept':'text/html'}});
        if(!response.ok)throw new Error('Formulir tidak dapat dimuat.');
        const page=pageContent(await response.text()),section=page.querySelector('.page-wrap');
        if(!section)throw new Error('Konten formulir tidak ditemukan.');
        content.innerHTML=section.innerHTML;
        initializeCompanyForm(content);
        const incomingForm=content.querySelector('form.letter-form-card');
        if(incomingForm){
            incomingForm.style.setProperty('box-sizing','border-box','important');
            incomingForm.style.setProperty('width','100%','important');
            incomingForm.style.setProperty('max-width','none','important');
            incomingForm.style.setProperty('margin-left','0','important');
            incomingForm.style.setProperty('margin-right','0','important');
        }
        modal.dataset.createUrl=url.href;
        updateOutgoingPreview(content);
        updateEventCertificatePreview(content);
    }catch(error){content.innerHTML=`<div class="alert alert-error">${error.message}</div>`}
});
document.addEventListener('submit',async event=>{
    const issueForm=event.target.closest('form.student-complete-form');
    if(!issueForm||event.defaultPrevented)return;
    event.preventDefault();
    if(issueForm.dataset.busy==='true')return;
    issueForm.dataset.busy='true';
    const button=issueForm.querySelector('button[type="submit"]');
    if(button)button.disabled=true;
    try{
        const data=new FormData(issueForm);
        const response=await fetch(issueForm.action,{method:'POST',body:data,headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'}});
        const result=await response.json().catch(()=>null);
        if(!response.ok)throw new Error(result?.message||(result?.errors?Object.values(result.errors).flat().join(' '):`Sertifikat gagal diterbitkan (HTTP ${response.status}).`));
        const previewUrl=result?.redirect;
        if(!previewUrl)throw new Error('Sertifikat terbit, tetapi alamat pratinjau tidak tersedia.');
        const previewResponse=await fetch(previewUrl,{headers:{'X-Requested-With':'XMLHttpRequest','Accept':'text/html'}});
        if(!previewResponse.ok)throw new Error('Sertifikat terbit, tetapi pratinjau tidak dapat dimuat.');
        const previewPage=pageContent(await previewResponse.text()),preview=previewPage.querySelector('.modal-record-preview');
        if(!preview)throw new Error('Sertifikat terbit, tetapi pratinjau tidak ditemukan.');
        const modal=ensureCreateModal(),content=modal.querySelector('.create-modal-content');
        modal.dataset.modalKind='pkl-certificate-preview';
        modal.dataset.previewUrl=previewUrl;
        modal.setAttribute('aria-label','Pratinjau sertifikat PKL');
        content.innerHTML=preview.outerHTML;
        openCreateModal(modal);
        try{
            const tableResponse=await fetch(location.href,{headers:{'X-Requested-With':'XMLHttpRequest','Accept':'text/html'}});
            if(tableResponse.ok){const tablePage=pageContent(await tableResponse.text()),newTable=tablePage.querySelector('.table-card'),oldTable=document.querySelector('.table-card');if(newTable&&oldTable)oldTable.replaceWith(newTable)}
        }catch{}
        if(result?.message){const notice=document.createElement('div');notice.className='alert alert-success';notice.textContent=result.message;content.prepend(notice)}
    }catch(error){
        const alert=document.createElement('div');alert.className='alert alert-error';alert.textContent=error.message;
        const pageWrap=document.querySelector('.page-wrap');if(pageWrap)pageWrap.prepend(alert);
    }finally{
        delete issueForm.dataset.busy;
        if(button)button.disabled=false;
    }
});
document.addEventListener('submit',async event=>{
    const modal=event.target.closest('.create-modal');
    if(!modal||event.defaultPrevented||!(event.target instanceof HTMLFormElement))return;
    const form=event.target;
    if(form.target==='_blank'||form.hasAttribute('data-native-submit'))return;
    if(modal.dataset.modalKind==='company-edit'&&form.matches('.company-delete-form')){
        event.preventDefault();
        const content=modal.querySelector('.create-modal-content'),data=new FormData(form);
        try{
            const response=await fetch(form.action,{method:'POST',body:data,headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'}});
            const result=await response.json().catch(()=>null);
            if(!response.ok)throw new Error(result?.message||`Mitra gagal dihapus (HTTP ${response.status}).`);
            modal.close();
            try{
                const tableResponse=await fetch(location.href,{headers:{'X-Requested-With':'XMLHttpRequest','Accept':'text/html'}});
                if(tableResponse.ok){const tablePage=pageContent(await tableResponse.text()),newTable=tablePage.querySelector('.table-card'),oldTable=document.querySelector('.table-card');if(newTable&&oldTable)oldTable.replaceWith(newTable)}
            }catch{}
            const pageWrap=document.querySelector('.page-wrap');
            if(result?.message&&pageWrap){const notice=document.createElement('div');notice.className='alert alert-success';notice.textContent=result.message;pageWrap.prepend(notice)}
        }catch(error){const alert=document.createElement('div');alert.className='alert alert-error';alert.textContent=error.message;content.prepend(alert)}
        return;
    }
    if(form.hasAttribute('data-disposition-required')&&!validateDispositionForm(form)){event.preventDefault();return}
    event.preventDefault();
    const formAction=form.getAttribute('action')||window.location.href;
    const submitter=event.submitter;
    if(submitter?.hasAttribute('data-confirm')&&!window.confirm(submitter.dataset.confirm))return;
    const content=modal.querySelector('.create-modal-content');
    modal.setAttribute('aria-busy','true');
    try{
        const data=new FormData(form);
        if(submitter?.name)data.append(submitter.name,submitter.value);
        if(modal.dataset.modalKind==='outgoing-edit'&&form.matches('.outgoing-letter-form')){
            content.querySelectorAll('.alert-error').forEach(alert=>alert.remove());
            const response=await fetch(formAction,{method:'POST',body:data,headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'}});
            const result=await response.json().catch(()=>null);
            if(response.status===422){
                const messages=result?.errors?Object.values(result.errors).flat():[];
                const alert=document.createElement('div');alert.className='alert alert-error';
                const heading=document.createElement('b');heading.textContent='Periksa kembali data surat.';alert.append(heading);
                if(messages.length){const list=document.createElement('ul');messages.forEach(message=>{const item=document.createElement('li');item.textContent=message;list.append(item)});alert.append(list)}
                content.prepend(alert);alert.scrollIntoView({block:'nearest'});return;
            }
            if(!response.ok)throw new Error(result?.message||`Surat gagal diperbarui (HTTP ${response.status}).`);
            const previewResponse=await fetch(modal.dataset.previewUrl,{headers:{'X-Requested-With':'XMLHttpRequest','Accept':'text/html'}});
            const previewPage=pageContent(await previewResponse.text()),preview=previewPage.querySelector('.modal-record-preview');
            if(!previewResponse.ok||!preview)throw new Error('Surat tersimpan, tetapi rincian surat tidak dapat diperbarui.');
            modal.dataset.modalKind='outgoing-detail';
            modal.setAttribute('aria-label','Rincian surat keluar');
            content.innerHTML=preview.outerHTML;
            if(result?.message){const notice=document.createElement('div');notice.className='alert alert-success';notice.textContent=result.message;content.prepend(notice)}
            try{
                const tableResponse=await fetch(location.href,{headers:{'X-Requested-With':'XMLHttpRequest','Accept':'text/html'}});
                if(tableResponse.ok){const tablePage=pageContent(await tableResponse.text()),newTable=tablePage.querySelector('.table-card'),oldTable=document.querySelector('.table-card');if(newTable&&oldTable)oldTable.replaceWith(newTable)}
            }catch{}
            return;
        }
        if(modal.dataset.modalKind==='incoming-edit'&&form.matches('.incoming-letter-form')){
            content.querySelectorAll('.alert-error').forEach(alert=>alert.remove());
            const response=await fetch(formAction,{method:'POST',body:data,headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'}});
            const result=await response.json().catch(()=>null);
            if(response.status===422){
                const messages=result?.errors?Object.values(result.errors).flat():[];
                const alert=document.createElement('div');alert.className='alert alert-error';
                const heading=document.createElement('b');heading.textContent='Periksa kembali data surat.';alert.append(heading);
                if(messages.length){const list=document.createElement('ul');messages.forEach(message=>{const item=document.createElement('li');item.textContent=message;list.append(item)});alert.append(list)}
                content.prepend(alert);alert.scrollIntoView({block:'nearest'});return;
            }
            if(!response.ok)throw new Error(result?.message||`Data surat gagal diperbarui (HTTP ${response.status}).`);
            const previewResponse=await fetch(modal.dataset.previewUrl,{headers:{'X-Requested-With':'XMLHttpRequest','Accept':'text/html'}});
            const previewPage=pageContent(await previewResponse.text()),preview=previewPage.querySelector('.modal-record-preview');
            if(!previewResponse.ok||!preview)throw new Error('Data tersimpan, tetapi rincian surat tidak dapat dimuat.');
            modal.dataset.modalKind='incoming-detail';
            content.innerHTML=preview.outerHTML;
            layoutDispositionForm(content);
            const notice=document.createElement('div');notice.className='alert alert-success';notice.textContent=result?.message||'Data surat diperbarui.';content.prepend(notice);
            try{
                const tableResponse=await fetch(location.href,{headers:{'X-Requested-With':'XMLHttpRequest','Accept':'text/html'}});
                if(tableResponse.ok){const tablePage=pageContent(await tableResponse.text()),newTable=tablePage.querySelector('.table-card'),oldTable=document.querySelector('.table-card');if(newTable&&oldTable)oldTable.replaceWith(newTable)}
            }catch{}
            return;
        }
        if(modal.dataset.modalKind==='incoming-detail'){
            content.querySelectorAll('.alert-error').forEach(alert=>alert.remove());
            const response=await fetch(formAction,{method:(form.method||'POST').toUpperCase(),body:data,headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'}});
            if(response.status===422){
                const result=await response.json().catch(()=>null),messages=result?.errors?Object.values(result.errors).flat():[];
                const alert=document.createElement('div');alert.className='alert alert-error';
                const heading=document.createElement('b');heading.textContent='Periksa kembali data disposisi.';alert.append(heading);
                if(messages.length){const list=document.createElement('ul');messages.forEach(message=>{const item=document.createElement('li');item.textContent=message;list.append(item)});alert.append(list)}
                content.prepend(alert);alert.scrollIntoView({block:'nearest'});return;
            }
            if(!response.ok)throw new Error(`Tindakan gagal (HTTP ${response.status}).`);
            const result=await response.json().catch(()=>null);
            const previewResponse=await fetch(modal.dataset.previewUrl,{headers:{'X-Requested-With':'XMLHttpRequest','Accept':'text/html'}});
            const previewPage=pageContent(await previewResponse.text()),preview=previewPage.querySelector('.modal-record-preview');
            if(!previewResponse.ok||!preview)throw new Error('Disposisi tersimpan, tetapi rincian surat tidak dapat diperbarui.');
            content.innerHTML=preview.outerHTML;
            layoutDispositionForm(content);
            const notice=document.createElement('div');notice.className='alert alert-success';
            notice.textContent=result?.message||(formAction.includes('follow-up-confirmation')?'Tindak lanjut dikonfirmasi.':data.get('_method')==='put'?'Disposisi diperbarui.':'Disposisi berhasil dibuat.');
            content.prepend(notice);
            try{
                const tableResponse=await fetch(location.href,{headers:{'X-Requested-With':'XMLHttpRequest','Accept':'text/html'}});
                if(tableResponse.ok){const tablePage=pageContent(await tableResponse.text()),newTable=tablePage.querySelector('.table-card'),oldTable=document.querySelector('.table-card');if(newTable&&oldTable)oldTable.replaceWith(newTable)}
            }catch{}
            return;
        }
        if(modal.dataset.modalKind==='pkl-certificate-preview'&&form.matches('.certificate-signatory-form')){
            content.querySelectorAll('.alert-error').forEach(alert=>alert.remove());
            const response=await fetch(formAction,{method:'POST',body:data,headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'}});
            const result=await response.json().catch(()=>null);
            if(response.status===422){
                const messages=result?.errors?Object.values(result.errors).flat():[];
                const alert=document.createElement('div');alert.className='alert alert-error';alert.textContent=messages.join(' ')||'Periksa kembali data penanda tangan.';content.prepend(alert);alert.scrollIntoView({block:'nearest'});return;
            }
            if(!response.ok)throw new Error(result?.message||`Data penanda tangan gagal disimpan (HTTP ${response.status}).`);
            const previewResponse=await fetch(modal.dataset.previewUrl,{headers:{'X-Requested-With':'XMLHttpRequest','Accept':'text/html'}});
            const previewPage=pageContent(await previewResponse.text()),preview=previewPage.querySelector('.modal-record-preview');
            if(!previewResponse.ok||!preview)throw new Error('Data tersimpan, tetapi pratinjau sertifikat tidak dapat diperbarui.');
            content.innerHTML=preview.outerHTML;
            if(result?.message){const notice=document.createElement('div');notice.className='alert alert-success';notice.textContent=result.message;content.prepend(notice)}
            return;
        }
        if(modal.dataset.modalKind==='pkl-certificate-preview'&&form.matches('.student-certificate-delete-form')){
            if(!window.confirm('Hapus data siswa beserta sertifikat PKL-nya? Tindakan ini tidak bisa dibatalkan.'))return;
            const response=await fetch(formAction,{method:'POST',body:data,headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'}});
            const result=await response.json().catch(()=>null);
            if(!response.ok)throw new Error(result?.message||`Siswa gagal dihapus (HTTP ${response.status}).`);
            modal.close();
            try{
                const tableResponse=await fetch(location.href,{headers:{'X-Requested-With':'XMLHttpRequest','Accept':'text/html'}});
                if(tableResponse.ok){const tablePage=pageContent(await tableResponse.text()),newTable=tablePage.querySelector('.table-card'),oldTable=document.querySelector('.table-card');if(newTable&&oldTable)oldTable.replaceWith(newTable)}
            }catch{}
            const pageWrap=document.querySelector('.page-wrap');
            if(result?.message&&pageWrap){const notice=document.createElement('div');notice.className='alert alert-success';notice.textContent=result.message;pageWrap.prepend(notice)}
            return;
        }
        if(modal.dataset.modalKind==='certificate-preview'&&form.matches('.event-certificate-delete-form')){
            if(!window.confirm('Hapus sertifikat ini? Tindakan ini tidak bisa dibatalkan.'))return;
            const response=await fetch(formAction,{method:'POST',body:data,headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'}});
            const result=await response.json().catch(()=>null);
            if(!response.ok)throw new Error(result?.message||`Sertifikat gagal dihapus (HTTP ${response.status}).`);
            modal.close();
            try{
                const tableResponse=await fetch(location.href,{headers:{'X-Requested-With':'XMLHttpRequest','Accept':'text/html'}});
                if(tableResponse.ok){const tablePage=pageContent(await tableResponse.text()),newTable=tablePage.querySelector('.table-card'),oldTable=document.querySelector('.table-card');if(newTable&&oldTable)oldTable.replaceWith(newTable)}
            }catch{}
            const pageWrap=document.querySelector('.page-wrap');
            if(result?.message&&pageWrap){const notice=document.createElement('div');notice.className='alert alert-success';notice.textContent=result.message;pageWrap.prepend(notice)}
            return;
        }
        if(modal.dataset.modalKind==='outgoing-detail'&&form.matches('.outgoing-delete-form')){
            if(!window.confirm('Hapus surat keluar ini? Tindakan ini tidak bisa dibatalkan.'))return;
            const response=await fetch(formAction,{method:'POST',body:data,headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'}});
            const result=await response.json().catch(()=>null);
            if(!response.ok)throw new Error(result?.message||`Surat gagal dihapus (HTTP ${response.status}).`);
            modal.close();
            try{
                const tableResponse=await fetch(location.href,{headers:{'X-Requested-With':'XMLHttpRequest','Accept':'text/html'}});
                if(tableResponse.ok){const tablePage=pageContent(await tableResponse.text()),newTable=tablePage.querySelector('.table-card'),oldTable=document.querySelector('.table-card');if(newTable&&oldTable)oldTable.replaceWith(newTable)}
            }catch{}
            const pageWrap=document.querySelector('.page-wrap');
            if(result?.message&&pageWrap){const notice=document.createElement('div');notice.className='alert alert-success';notice.textContent=result.message;pageWrap.prepend(notice)}
            return;
        }
        if(modal.dataset.modalKind==='outgoing-detail'&&form.matches('.outgoing-submit-form,.outgoing-review-form')){
            if(form.matches('.outgoing-review-form')&&data.get('decision')==='revisi'){
                const noteName=formAction.includes('/verify')?'verification_note':'kepsek_note';
                const note=form.elements.namedItem(noteName);
                if(!String(data.get(noteName)||'').trim()){
                    content.querySelectorAll('.outgoing-validation-error').forEach(alert=>alert.remove());
                    const alert=document.createElement('div');alert.className='alert alert-error outgoing-validation-error';
                    alert.textContent=noteName==='verification_note'?'Catatan untuk TU wajib diisi jika meminta revisi.':'Catatan revisi wajib diisi sebelum surat dikembalikan ke TU.';
                    form.prepend(alert);note?.focus();return;
                }
            }
            const response=await fetch(formAction,{method:'POST',body:data,headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'}});
            const result=await response.json().catch(()=>null);
            if(response.status===422){
                content.querySelectorAll('.outgoing-validation-error').forEach(alert=>alert.remove());
                const alert=document.createElement('div');alert.className='alert alert-error outgoing-validation-error';
                alert.textContent=Object.entries(result?.errors||{}).flatMap(([field,messages])=>messages.map(message=>field==='verification_note'?'Catatan untuk TU wajib diisi jika meminta revisi.':field==='kepsek_note'?'Catatan revisi wajib diisi sebelum surat dikembalikan ke TU.':message)).join(' ' )||'Periksa kembali data yang diisi.';
                form.prepend(alert);alert.scrollIntoView({block:'nearest'});return;
            }
            if(!response.ok)throw new Error(result?.message||`Surat gagal dikirim (HTTP ${response.status}).`);
            const previewResponse=await fetch(modal.dataset.previewUrl,{headers:{'X-Requested-With':'XMLHttpRequest','Accept':'text/html'}});
            const previewPage=pageContent(await previewResponse.text()),preview=previewPage.querySelector('.modal-record-preview');
            if(!previewResponse.ok||!preview)throw new Error('Surat terkirim, tetapi rincian tidak dapat diperbarui.');
            content.innerHTML=preview.outerHTML;
            if(result?.message){const notice=document.createElement('div');notice.className='alert alert-success';notice.textContent=result.message;content.prepend(notice)}
            try{
                const tableResponse=await fetch(location.href,{headers:{'X-Requested-With':'XMLHttpRequest','Accept':'text/html'}});
                if(tableResponse.ok){const tablePage=pageContent(await tableResponse.text()),newTable=tablePage.querySelector('.table-card'),oldTable=document.querySelector('.table-card');if(newTable&&oldTable)oldTable.replaceWith(newTable)}
            }catch{}
            return;
        }
        const isOutgoingForm=form.classList.contains('outgoing-letter-form');
        const response=await fetch(formAction,{method:(form.method||'POST').toUpperCase(),body:data,headers:{'X-Requested-With':'XMLHttpRequest','Accept':isOutgoingForm?'application/json':'text/html'}});
        if(isOutgoingForm){
            const result=await response.json().catch(()=>null);
            if(response.ok&&result?.redirect){
                const pageResponse=await fetch(result.redirect,{headers:{'X-Requested-With':'XMLHttpRequest','Accept':'text/html'}});
                const page=pageContent(await pageResponse.text()),section=page.querySelector('.page-wrap'),preview=page.querySelector('.modal-record-preview');
                if(pageResponse.ok&&preview){
                    modal.dataset.modalKind='outgoing-detail';
                    modal.dataset.previewUrl=result.redirect;
                    modal.setAttribute('aria-label','Rincian surat keluar');
                    content.innerHTML=preview.outerHTML;
                    if(result.message){const notice=document.createElement('div');notice.className='alert alert-success';notice.textContent=result.message;content.prepend(notice)}
                    try{
                        const tableResponse=await fetch(location.href,{headers:{'X-Requested-With':'XMLHttpRequest','Accept':'text/html'}});
                        if(tableResponse.ok){const tablePage=pageContent(await tableResponse.text()),newTable=tablePage.querySelector('.table-card'),oldTable=document.querySelector('.table-card');if(newTable&&oldTable)oldTable.replaceWith(newTable)}
                    }catch{}
                    return;
                }
                if(!pageResponse.ok||!section)throw new Error('Surat tersimpan, tetapi halaman surat tidak dapat dimuat. Buka kembali modul surat keluar.');
                const current=document.querySelector('.page-wrap');
                if(current)current.innerHTML=section.innerHTML;
                history.pushState({},'',result.redirect);
                copyPageTitle(page);
                if(result.message&&current){const notice=document.createElement('div');notice.className='alert alert-success';notice.textContent=result.message;current.prepend(notice)}
                modal.close();
                return;
            }
            const messages=result?.errors?Object.values(result.errors).flat():[];
            if(messages.length){
                const alert=document.createElement('div');alert.className='alert alert-error';const heading=document.createElement('b');heading.textContent='Periksa kembali data yang diisi.';const list=document.createElement('ul');messages.forEach(message=>{const item=document.createElement('li');item.textContent=message;list.append(item)});alert.append(heading,list);content.prepend(alert);alert.scrollIntoView({block:'nearest'});return;
            }
            throw new Error(result?.message||`Penyimpanan gagal (HTTP ${response.status}). Periksa koneksi lalu coba lagi.`);
        }
        const page=pageContent(await response.text()),section=page.querySelector('.page-wrap');
        if(!section)throw new Error('Respons formulir tidak dapat ditampilkan.');
        if(response.url!==modal.dataset.createUrl){
            const current=document.querySelector('.page-wrap');
            if(current)current.innerHTML=section.innerHTML;
            history.pushState({},'',response.url);
            copyPageTitle(page);
            modal.close();
        }else{
            content.innerHTML=section.innerHTML;
            copyPageTitle(page);
            initializeCompanyForm(content);
            updateOutgoingPreview(content);
        }
    }catch(error){content.insertAdjacentHTML('afterbegin',`<div class="alert alert-error">${error.message}</div>`)}
    finally{modal.removeAttribute('aria-busy')}
});
document.addEventListener('input',event=>{if(event.target.closest('.outgoing-letter-form'))updateOutgoingPreview(event.target.closest('.create-modal')||document)});
document.addEventListener('input',event=>{if(event.target.closest('.event-certificate-form'))updateEventCertificatePreview(event.target.closest('.create-modal')||document)});
document.addEventListener('change',event=>{
    const form=event.target.closest('.event-certificate-form');
    if(!form)return;
    if(event.target.matches('[name="category"]')){
        const title=form.elements.namedItem('title');
        const defaultTitles=['Sertifikat Penghargaan','Sertifikat Peserta Kegiatan','Sertifikat Narasumber','Sertifikat Kegiatan'];
        const nextTitle=({penghargaan:'Sertifikat Penghargaan',peserta:'Sertifikat Peserta Kegiatan',narasumber:'Sertifikat Narasumber',lainnya:'Sertifikat Kegiatan'})[event.target.value];
        if(title&&defaultTitles.includes(title.value.trim()))title.value=nextTitle;
    }
    updateEventCertificatePreview(event.target.closest('.create-modal')||document);
});
document.addEventListener('change',event=>{
    const form=event.target.closest('.outgoing-letter-form');
    if(!form)return;
    if(event.target.matches('#student-record')){
        const option=event.target.selectedOptions[0];
        if(option?.dataset.studentName){form.elements.namedItem('recipient_name').value=option.dataset.studentName;form.elements.namedItem('details[birth_place]').value=option.dataset.birthPlace||'';form.elements.namedItem('details[birth_date]').value=option.dataset.birthDate||''}
    }
    updateOutgoingPreview(event.target.closest('.create-modal')||document);
});
document.addEventListener('click',event=>{
    const addRecipient=event.target.closest('[data-add-task-recipient]');
    if(addRecipient){const list=addRecipient.closest('.task-recipient-field')?.querySelector('[data-task-recipient-list]'),template=document.getElementById('task-recipient-template');if(!list||!template)return;const index=Number(list.dataset.nextIndex||1),row=template.content.cloneNode(true);row.querySelectorAll('[data-task-recipient-field]').forEach(input=>{input.name=`details[recipients][${index}][${input.dataset.taskRecipientField}]`});list.dataset.nextIndex=String(index+1);list.append(row);updateOutgoingPreview(addRecipient.closest('.create-modal')||document);return}
    const removeRecipient=event.target.closest('[data-remove-task-recipient]');
    if(removeRecipient){removeRecipient.closest('.task-recipient-input-row')?.remove();updateOutgoingPreview(removeRecipient.closest('.create-modal')||document);return}
    const add=event.target.closest('[data-add-task-participant]');
    if(add){const list=add.closest('.task-participant-field')?.querySelector('[data-task-participant-list]'),template=document.getElementById('task-participant-template');if(!list||!template)return;const index=Number(list.dataset.nextIndex||0),row=template.content.cloneNode(true);row.querySelector('[data-task-participant-name]').name=`details[participants][${index}][name]`;row.querySelector('[data-task-participant-class]').name=`details[participants][${index}][class]`;list.dataset.nextIndex=String(index+1);list.append(row);updateOutgoingPreview(add.closest('.create-modal')||document);return}
    const remove=event.target.closest('[data-remove-task-participant]');
    if(remove){remove.closest('.task-participant-input-row')?.remove();updateOutgoingPreview(remove.closest('.create-modal')||document)}
});
window.addEventListener('resize',()=>document.querySelectorAll('.letter-live-preview').forEach(fitLetterPreview));
updateOutgoingPreview();
fitEventCertificateTitles();

let tableSortPending=false;
let liveSearchTimer;
let liveSearchController;
async function refreshSortedTable(url,updateHistory=true,replaceFilter=false){
    const currentTable=document.querySelector('.table-card');
    if(!currentTable)return false;
    currentTable.setAttribute('aria-busy','true');
    try{
        const response=await fetch(url,{headers:{'X-Requested-With':'XMLHttpRequest','Accept':'text/html'}});
        if(!response.ok)throw new Error('Tabel gagal diperbarui.');
        const page=new DOMParser().parseFromString(await response.text(),'text/html');
        const nextTable=page.querySelector('.table-card');
        if(!nextTable)throw new Error('Tabel tidak ditemukan pada respons.');
        currentTable.replaceWith(nextTable);
        if(replaceFilter){
            const currentFilter=document.querySelector('.js-ajax-table-filter');
            const nextFilter=page.querySelector('.js-ajax-table-filter');
            if(currentFilter&&nextFilter)currentFilter.replaceWith(nextFilter);
        }
        if(updateHistory)history.pushState({},'',url);
        return true;
    }catch{
        currentTable.removeAttribute('aria-busy');
        return false;
    }
}
document.addEventListener('input',event=>{
    const search=event.target.closest('.js-ajax-table-filter input[name="q"]');
    if(!search)return;
    clearTimeout(liveSearchTimer);
    liveSearchTimer=setTimeout(async()=>{
        const form=search.form;
        if(!form)return;
        const url=new URL(form.action||window.location.href,window.location.href);
        url.search='';
        for(const [name,value] of new FormData(form))if(String(value).trim())url.searchParams.append(name,String(value).trim());
        liveSearchController?.abort();
        liveSearchController=new AbortController();
        const controller=liveSearchController;
        const table=document.querySelector('.table-card');
        if(!table)return;
        table.setAttribute('aria-busy','true');
        try{
            const response=await fetch(url.href,{signal:controller.signal,headers:{'X-Requested-With':'XMLHttpRequest','Accept':'text/html'}});
            if(!response.ok)throw new Error('Tabel gagal diperbarui.');
            const page=new DOMParser().parseFromString(await response.text(),'text/html');
            const nextTable=page.querySelector('.table-card');
            if(!nextTable||controller!==liveSearchController)return;
            table.replaceWith(nextTable);
            history.replaceState({},'',url.href);
        }catch(error){
            if(error.name!=='AbortError')table.removeAttribute('aria-busy');
        }
    },350);
});
document.addEventListener('change',async event=>{
    const select=event.target.closest('.js-ajax-table-filter select');
    if(!select||tableSortPending)return;
    const form=select.form;
    if(!form)return;
    clearTimeout(liveSearchTimer);
    liveSearchController?.abort();
    const url=new URL(form.action||window.location.href,window.location.href);
    url.search='';
    for(const [name,value] of new FormData(form))if(String(value).trim())url.searchParams.append(name,String(value).trim());
    tableSortPending=true;
    const refreshed=await refreshSortedTable(url.href,true,false);
    tableSortPending=false;
    if(!refreshed)window.location.assign(url.href);
});
document.addEventListener('submit',async event=>{
    const form=event.target.closest('.js-ajax-table-filter');
    if(!form||tableSortPending)return;
    event.preventDefault();
    const url=new URL(form.action||window.location.href,window.location.href);
    url.search='';
    for(const [name,value] of new FormData(form))if(String(value).trim())url.searchParams.append(name,String(value).trim());
    tableSortPending=true;
    const refreshed=await refreshSortedTable(url.href,true,true);
    tableSortPending=false;
    if(!refreshed)window.location.assign(url.href);
});
document.addEventListener('click',async event=>{
    const reset=event.target.closest('.js-ajax-table-filter .filter-reset');
    if(!reset||event.button!==0||event.ctrlKey||event.metaKey||event.shiftKey||event.altKey||tableSortPending)return;
    event.preventDefault();
    tableSortPending=true;
    const refreshed=await refreshSortedTable(reset.href,true,true);
    tableSortPending=false;
    if(!refreshed)window.location.assign(reset.href);
});
document.addEventListener('click',async event=>{
    const link=event.target.closest('.sortable-heading');
    if(!link||event.button!==0||event.ctrlKey||event.metaKey||event.shiftKey||event.altKey||tableSortPending)return;
    event.preventDefault();
    tableSortPending=true;
    const url=link.href;
    const refreshed=await refreshSortedTable(url);
    tableSortPending=false;
    if(!refreshed)window.location.assign(url);
});
window.addEventListener('popstate',async()=>{await refreshSortedTable(window.location.href,false,!!document.querySelector('.js-ajax-table-filter'))});

document.addEventListener('click', event => {
    const link = event.target.closest('.js-print-disposition');
    if (!link || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
    event.preventDefault();

    const frame = document.createElement('iframe');
    frame.setAttribute('aria-hidden', 'true');
    frame.title = 'Dokumen lembar disposisi untuk dicetak';
    frame.style.cssText = 'position:fixed;width:0;height:0;border:0;right:0;bottom:0;visibility:hidden';
    frame.onload = () => {
        const printWindow = frame.contentWindow;
        if (!printWindow) return frame.remove();
        printWindow.addEventListener('afterprint', () => frame.remove(), { once: true });
        printWindow.focus();
        printWindow.print();
    };
    frame.src = link.href;
    document.body.appendChild(frame);
});

function fitEventCertificateTitles(){document.querySelectorAll('.event-certificate-title').forEach(title=>{title.style.fontSize='';const base=parseFloat(getComputedStyle(title).fontSize);if(!base||!title.clientWidth||!title.clientHeight)return;let size=base;const minimum=Math.max(8,base*.35);title.style.fontSize=`${size}px`;while((title.scrollHeight>title.clientHeight+1||title.scrollWidth>title.clientWidth+1)&&size>minimum){size=Math.max(minimum,size-1);title.style.fontSize=`${size}px`}})}
function scheduleEventCertificateTitleFit(){requestAnimationFrame(fitEventCertificateTitles)}
window.addEventListener('load',scheduleEventCertificateTitleFit);
window.addEventListener('resize',scheduleEventCertificateTitleFit);
window.addEventListener('beforeprint',scheduleEventCertificateTitleFit);
window.addEventListener('afterprint',scheduleEventCertificateTitleFit);
if(document.fonts?.ready)document.fonts.ready.then(scheduleEventCertificateTitleFit);
scheduleEventCertificateTitleFit();

let documentTitleBeforePrint=null;
function printableDocumentTitle(){
    const base=(document.title.split(/\s+[—–]\s+/)[0]||'Dokumen').trim();
    const report=document.querySelector('.report-print-title');
    const disposition=document.querySelector('.disposition-copy');
    const outgoing=document.querySelector('.letter-paper');
    const certificates=[...document.querySelectorAll('.certificate')];
    let title=base;
    if(report){
        const content=report.textContent.replace(/\s+/g,' ').trim();
        const period=content.split('—')[1]?.split('·')[0]?.trim();
        title=period?`Laporan - ${period}`:'Laporan Administrasi';
    }else if(disposition){
        const agenda=disposition.querySelector('.disposition-info tr:nth-child(2) td:nth-child(4)')?.textContent.trim();
        title=agenda?`Disposisi - ${agenda}`:'Lembar Disposisi';
    }else if(outgoing){
        title=outgoing.dataset.printTitle||`Surat Keluar - ${base.replace(/^Pratinjau\s*/i,'')}`;
    }else if(certificates.length){
        const eventTitle=document.querySelector('.event-certificate-title')?.textContent.trim();
        const names=[...document.querySelectorAll('.certificate .student-name')].map(node=>node.textContent.trim()).filter(Boolean);
        if(eventTitle)title=certificates.length===1?`Sertifikat Kegiatan - ${eventTitle} - ${names[0]||''}`:`Sertifikat Kegiatan - ${certificates.length} Sertifikat`;
        else title=certificates.length===1?`Sertifikat PKL - ${names[0]||''}`:`Sertifikat PKL - ${certificates.length} Siswa`;
    }
    return title.replace(/[\\/:*?"<>|]+/g,'-').replace(/\s+/g,' ').replace(/[. ]+$/,'').slice(0,120)||'Dokumen';
}
window.addEventListener('beforeprint',()=>{
    if(documentTitleBeforePrint===null)documentTitleBeforePrint=document.title;
    document.title=printableDocumentTitle();
});
window.addEventListener('afterprint',()=>{
    if(documentTitleBeforePrint!==null){document.title=documentTitleBeforePrint;documentTitleBeforePrint=null}
});
const nativeBrowserPrint=window.print.bind(window);
window.print=function(){
    if(documentTitleBeforePrint===null)documentTitleBeforePrint=document.title;
    document.title=printableDocumentTitle();
    nativeBrowserPrint();
};

document.addEventListener('click',event=>{const button=event.target.closest('[data-password-toggle]');if(!button)return;const input=document.getElementById(button.dataset.passwordToggle);if(!input)return;const show=input.type==='password';input.type=show?'text':'password';button.setAttribute('aria-pressed',String(show));button.classList.toggle('is-visible',show);button.setAttribute('aria-label',show?'Sembunyikan kata sandi':'Tampilkan kata sandi');input.focus()});

function validateDispositionForm(form){
    const recipients=Array.from(form.elements.namedItem('recipients[]')||[]);
    const otherRecipient=form.elements.namedItem('other_recipient');
    const hasRecipient=recipients.some(input=>input.checked)||Boolean(otherRecipient?.value.trim());
    if(recipients[0])recipients[0].setCustomValidity(hasRecipient?'':'Pilih minimal satu penerima atau isi penerima lainnya.');
    const instructions=Array.from(form.elements.namedItem('instruction')||[]);
    const hasInstruction=instructions.some(input=>input.checked);
    if(instructions[0])instructions[0].setCustomValidity(hasInstruction?'':'Pilih arahan disposisi terlebih dahulu.');
    const otherInstruction=form.elements.namedItem('other_instruction');
    if(otherInstruction)otherInstruction.required=instructions.some(input=>input.checked&&input.value==='lainnya');
    return form.reportValidity();
}
document.addEventListener('submit',event=>{
    const form=event.target.closest('form[data-disposition-required]');
    if(form&&!form.closest('.create-modal')&&!validateDispositionForm(form))event.preventDefault();
});
document.addEventListener('input',event=>{
    const form=event.target.closest('form[data-disposition-required]');
    if(!form)return;
    const recipients=Array.from(form.elements.namedItem('recipients[]')||[]);
    const otherRecipient=form.elements.namedItem('other_recipient');
    const hasRecipient=recipients.some(input=>input.checked)||Boolean(otherRecipient?.value.trim());
    if(recipients[0])recipients[0].setCustomValidity(hasRecipient?'':'Pilih minimal satu penerima atau isi penerima lainnya.');
});
document.addEventListener('change',event=>{
    const form=event.target.closest('form[data-disposition-required]');
    if(!form)return;
    const recipients=Array.from(form.elements.namedItem('recipients[]')||[]);
    const otherRecipient=form.elements.namedItem('other_recipient');
    const hasRecipient=recipients.some(input=>input.checked)||Boolean(otherRecipient?.value.trim());
    if(recipients[0])recipients[0].setCustomValidity(hasRecipient?'':'Pilih minimal satu penerima atau isi penerima lainnya.');
    const instructions=Array.from(form.elements.namedItem('instruction')||[]);
    const hasInstruction=instructions.some(input=>input.checked);
    if(instructions[0])instructions[0].setCustomValidity(hasInstruction?'':'Pilih arahan disposisi terlebih dahulu.');
    const otherInstruction=form.elements.namedItem('other_instruction');
    if(otherInstruction)otherInstruction.required=instructions.some(input=>input.checked&&input.value==='lainnya');
});
