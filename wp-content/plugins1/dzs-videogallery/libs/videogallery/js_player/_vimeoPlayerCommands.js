

export function vimeoPlayerCommand(selfClass, command, value){

  if(!command){
    command = 'pause';
  }
  const vimeo_data = {
    "method": command
  };

  if(value!==undefined){
    vimeo_data.value = value;
  }

  if (selfClass.vimeo_url) {
    try {
      selfClass._videoElement.contentWindow.postMessage(JSON.stringify(vimeo_data), selfClass.vimeo_url);

      if(command==='pause' || (command==='seekTo' && value=='0')){

        selfClass.wasPlaying = false;
        selfClass.paused = true;
      }
    } catch (err) {
    }
  }
}