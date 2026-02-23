import {svg_aurora_play_btn} from "../../js_dzsvg/_dzsvg_svgs";

/**
 * draw fullscreen bars
 * @param selfClass
 * @param _controls_fs_canvas
 * @param argColor
 */
export function player_controls_drawFullscreenBarsOnCanvas(selfClass, _controls_fs_canvas, argColor) {


  if (selfClass.initOptions.design_skin !== 'skin_pro') {
    return;
  }
  var ctx = _controls_fs_canvas.getContext("2d");
  var ctx_w = _controls_fs_canvas.width;
  var ctx_pw = ctx_w / 100;
  var ctx_ph = ctx_w / 100;

  ctx.fillStyle = argColor;
  var borderw = 30;
  ctx.fillRect(25 * ctx_pw, 25 * ctx_ph, 50 * ctx_pw, 50 * ctx_ph);
  ctx.beginPath();
  ctx.moveTo(0, 0);
  ctx.lineTo(0, borderw * ctx_ph);
  ctx.lineTo(borderw * ctx_pw, 0);
  ctx.fill();
  ctx.moveTo(0, 100 * ctx_ph);
  ctx.lineTo(0, (100 - borderw) * ctx_ph);
  ctx.lineTo(borderw * ctx_pw, 100 * ctx_ph);
  ctx.fill();
  ctx.moveTo((100) * ctx_pw, (100) * ctx_ph);
  ctx.lineTo((100 - borderw) * ctx_pw, (100) * ctx_ph);
  ctx.lineTo((100) * ctx_pw, (100 - borderw) * ctx_ph);
  ctx.fill();
  ctx.moveTo((100) * ctx_pw, (0) * ctx_ph);
  ctx.lineTo((100 - borderw) * ctx_pw, (0) * ctx_ph);
  ctx.lineTo((100) * ctx_pw, (borderw) * ctx_ph);
  ctx.fill();

}

export function player_controls_stringScrubbar(){

  var str_scrubbar = '<div class="scrubbar">';
  str_scrubbar += '<div class="scrub-bg"></div><div class="scrub-buffer"></div><div class="scrub">';


  str_scrubbar += '</div><div class="scrubBox"></div><div class="scrubBox-prog"></div>';
  str_scrubbar += '</div>';

  return str_scrubbar;
}
export function player_controls_drawBigPlayBtn(){

  let string_structureBigPlayBtn = '<div class="big-play-btn">';
  string_structureBigPlayBtn += svg_aurora_play_btn;
  string_structureBigPlayBtn += '</div>';


  return string_structureBigPlayBtn;
}