'use strict';
const ConstantsDzsvg = {
  THREEJS_LIB_URL: 'https://cdnjs.cloudflare.com/ajax/libs/three.js/r73/three.min.js',
  THREEJS_LIB_ORBIT_URL: 'https://s3-us-west-2.amazonaws.com/s.cdpn.io/211120/orbitControls.js',
}


window.dzsvp_player_init360 = (selfClass) => {


  let videoTexture = null;
  let scene = null;
  let sphereMesh = null;
  let sphereMat = null;
  let sphereGeometry = null;
  let renderer = null;
  let camera = null;
  let controls = null
  ;


  /**
   * all dependencies met
   */
  function init_360player() {

    const SPHERE_RADIUS = window['DZSVP_' + 'SPHERE_RADIUS'] !== undefined ? window['DZSVP_' + 'SPHERE_RADIUS'] : 500;
    const CAMERA_NEAR = window['DZSVP_' + 'CAMERA_NEAR'] !== undefined ? window['DZSVP_' + 'CAMERA_NEAR'] : 1;
    const CAMERA_FAR = window['DZSVP_' + 'CAMERA_FAR'] !== undefined ? window['DZSVP_' + 'CAMERA_FAR'] : 1000;


    renderer = new THREE.WebGLRenderer({antialias: true});
    renderer.setSize(selfClass.totalWidth, selfClass.totalHeight);
    renderer.alpha = true;
    jQuery(selfClass._videoElement).after(renderer.domElement);
    jQuery(selfClass._videoElement).next().addClass('dzsvg-360-canvas');


    scene = new THREE.Scene();


    selfClass._videoElement.setAttribute('crossorigin', 'anonymous');
    videoTexture = new THREE.Texture(selfClass._videoElement);
    videoTexture.minFilter = THREE.LinearFilter;
    videoTexture.magFilter = THREE.LinearFilter;
    videoTexture.format = THREE.RGBFormat;

    videoTexture.center = new THREE.Vector2(0.5, 0.5);
    videoTexture.rotation = Math.PI * 2;
    videoTexture.flipY = false;

    sphereGeometry = new THREE.SphereGeometry(SPHERE_RADIUS, 60, 60, Math.PI * 2, -Math.PI * 2, Math.PI, -Math.PI);
    sphereMat = new THREE.MeshBasicMaterial({map: videoTexture});
    sphereMat.side = THREE.DoubleSide;

    sphereMesh = new THREE.Mesh(sphereGeometry, sphereMat);
    scene.add(sphereMesh);


    camera = new THREE.PerspectiveCamera(45, selfClass.totalWidth / selfClass.totalHeight, CAMERA_NEAR, CAMERA_FAR);
    camera.position.y = 0;
    camera.position.z = 500;

    scene.add(camera);

    controls = new THREE.OrbitControls(camera, renderer.domElement);

    controls.enableDamping = false;
    controls.enableRotate = false;
    controls.dampingFactor = 0.25;

    controls.enableZoom = true;
    controls.minDistance = 400;
    controls.maxDistance = 1000;

    controls.enabled = false;

    function render() {
      if (selfClass._videoElement.readyState === selfClass._videoElement.HAVE_ENOUGH_DATA) {
        videoTexture.needsUpdate = true;
      }
      controls.update();
      renderer.render(scene, camera);
      requestAnimationFrame(render);

    }

    render(selfClass);
  }


  var o = selfClass.initOptions;
  var self = this;

  selfClass.cthis.addClass('is-360');
  selfClass.get_responsive_ratio({
    'called_from': '360'
  });
  if (selfClass.totalHeight === 0 && o.responsive_ratio) {
    selfClass.totalHeight = Number(o.responsive_ratio) * selfClass.totalWidth;
  }


  jQuery.ajax({
    url: ConstantsDzsvg.THREEJS_LIB_URL,
    dataType: "script",
    success: function (arg) {


      jQuery.ajax({
        url: ConstantsDzsvg.THREEJS_LIB_ORBIT_URL,
        dataType: "script",
        success: function (arg) {
          init_360player();
        }
      });
    }
  });


  window.dzsvp_player_360_eventAfterQualityChange = function (selfClass) {

    console.log('dzsvp_player_360_eventAfterQualityChange()');
    const SPHERE_RADIUS = window['DZSVP_' + 'SPHERE_RADIUS'] !== undefined ? window['DZSVP_' + 'SPHERE_RADIUS'] : 500;
    selfClass._videoElement.setAttribute('crossorigin', 'anonymous');
    videoTexture = new THREE.Texture(selfClass._videoElement);
    videoTexture.minFilter = THREE.LinearFilter;
    videoTexture.magFilter = THREE.LinearFilter;
    videoTexture.format = THREE.RGBFormat;


    scene.remove(sphereMesh);

    sphereGeometry = new THREE.SphereGeometry(SPHERE_RADIUS, 60, 60);
    sphereMat = new THREE.MeshBasicMaterial({map: videoTexture});
    sphereMat.side = THREE.BackSide;
    sphereMesh = new THREE.Mesh(sphereGeometry, sphereMat);
    scene.add(sphereMesh);
  }


  window.dzsvp_player_360_funcResizeControls = function (warg, harg) {
    if (renderer) {
      camera.aspect = warg / harg;
      camera.updateProjectionMatrix();
      renderer.setSize(warg, harg);
    }
  }
  window.dzsvp_player_360_funcEnableControls = function () {
    if (controls) {
      controls.enabled = true;
    }
  }
  /**
   *
   * @param {DzsVideoPlayer} selfClass
   */
  window.dzsvp_player_360_eventFunctionsInit = (selfClass) => {


    var $ = jQuery;


    selfClass.cthis.on('touchstart', function (e) {
      if (e.originalEvent && e.originalEvent.target && $(e.originalEvent.target).hasClass('video-overlay')) {
        selfClass.cthis.addClass('mouse-is-out');

      }
      if (controls) {
        controls.enabled = true;
      }
    })
    $(document).on('touchend', function (e) {
      selfClass.cthis.removeClass('mouse-is-out');
      if (controls) {
        controls.enabled = false;
      }
    })

    setTimeout(() => {
      selfClass.handleResize();
    }, 1000);
  }
}


