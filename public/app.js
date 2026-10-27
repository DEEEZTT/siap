document.addEventListener('click',event=>{const target=event.target.closest('[data-confirm]');if(target&&!window.confirm(target.dataset.confirm))event.preventDefault()});document.addEventListener('submit',event=>{const form=event.target.closest('[data-confirm]');if(form&&!window.confirm(form.dataset.confirm))event.preventDefault()});

let tableSortPending=false;
async function refreshSortedTable(url,updateHistory=true){
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
        if(updateHistory)history.pushState({},'',url);
        return true;
    }catch{
        currentTable.removeAttribute('aria-busy');
        return false;
    }
}
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
window.addEventListener('popstate',async()=>{await refreshSortedTable(window.location.href,false)});

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
        title=`Surat Keluar - ${base.replace(/^Pratinjau\s*/i,'')}`;
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
