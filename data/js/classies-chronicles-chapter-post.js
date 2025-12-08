document.addEventListener("DOMContentLoaded", function() {
  (function(){
    var playBtn = document.getElementById('bj-play-btn');
    if(playBtn){
      playBtn.addEventListener('click', function(){
        var wrap = document.getElementById('bj-video-wrap');
        var iframe = document.createElement('iframe');
        iframe.setAttribute('src','https://www.youtube.com/embed/videoseries?list=PLWhLpL_Jf9f6LpHbdB7m7IYIiE9BCFprx&rel=0&modestbranding=1');
        iframe.setAttribute('title','Classies Chronicles playlist');
        iframe.setAttribute('frameborder','0');
        iframe.setAttribute('allow','accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture');
        iframe.setAttribute('allowfullscreen', 'true');
        iframe.style.width='100%';
        iframe.style.height='100%';
        iframe.style.position='absolute';
        iframe.style.left='0';
        iframe.style.top='0';
        wrap.innerHTML='';
        wrap.appendChild(iframe);
        try { if(typeof gtag==='function') gtag('event','playlist_play',{event_category:'engagement',event_label:'patient_infatuation'}); }catch(e){}
        try { if(window.dataLayer) window.dataLayer.push({event:'playlist_play', source:'patient_infatuation'}); }catch(e){}
      },{once:true});
    }

    var form=document.getElementById('bj-early-form');
    if(form){
      form.addEventListener('submit',function(e){
        e.preventDefault();
        var submit=document.getElementById('bj-early-submit');
        if(submit.disabled) return;
        var email=form.querySelector('input[name="email"]').value.trim();
        var name=form.querySelector('input[name="name"]').value.trim();
        var consent=document.getElementById('bj-consent').checked;
        if(!email||!consent){alert('Please provide email and consent.');return;}
        submit.disabled=true;
        var utm={utm_source:form.querySelector('input[name="utm_source"]').value,utm_medium:form.querySelector('input[name="utm_medium"]').value,utm_campaign:form.querySelector('input[name="utm_campaign"]').value};
        var regsUrl='https://buzzjuice.net/registration-orientation';
        var params=new URLSearchParams({email:email,name:name,...utm});
        window.open(regsUrl+'?'+params.toString(),'_blank');
        document.getElementById('bj-early-thanks').style.display='block';
        try{if(typeof gtag==='function') gtag('event','early_access_submit',{event_category:'engagement',event_label:'patient_infatuation'}); else if(window.dataLayer) window.dataLayer.push({event:'early_access_submit',source:'patient_infatuation'});}catch(e){}
        setTimeout(function(){submit.disabled=false;},3000);
      });
    }

    var pollCta=document.getElementById('bj-poll-cta');
    if(pollCta){
      pollCta.addEventListener('click',function(){
        try{if(typeof gtag==='function') gtag('event','poll_cta_click',{event_category:'engagement',event_label:'patient_infatuation_poll'});}catch(e){}
        try{if(window.dataLayer)window.dataLayer.push({event:'poll_cta_click',source:'patient_infatuation'});}catch(e){}
      });
    }

    var whatsapp=document.getElementById('whatsapp-share');
    if(whatsapp){
      whatsapp.addEventListener('click',function(){
        try{if(typeof gtag==='function') gtag('event','share_whatsapp',{event_category:'engagement',event_label:'patient_infatuation'});}catch(e){}
        try{if(window.dataLayer) window.dataLayer.push({event:'share_whatsapp',source:'patient_infatuation'});}catch(e){}
      });
    }
  })();
});
