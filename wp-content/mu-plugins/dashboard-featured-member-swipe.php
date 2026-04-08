<?php
/**
 * Plugin Name: Buzzjuice Featured Members Portrait Card
 * Description: [bz_featured_members_card] — Tinder/Badoo-style portrait swiper for newest monetizable members.
 * 
 * PROMPT>>
 * Instead of the default BuddyBoss 'Featured Members' widget section in the right sidebar, it would be good to have a portrait card, like the size of a tinder or Badoo card, that swipes newest to oldest WordPress members in descending order from newest to oldest members.

1. a left or right tap or left or right button 'within' the card borders would move to the next or previous member. Within the card means that the left and right buttons would appear on the user's profile picture.
2. Tapping on the username would redirect to the user's QuickDate profile at https://buzzjuice.net/social/@{username}
3. An 'Explore Matches' button below the card would redirect to the QuickDate homepage at https://buzzjuice.net/social/
4. The title 'Featured Members'.
5. The user's avatar should appear as a full portrait size picture of the user such as appears on tinder or Badoo, being full width on mobile.
6. 'New badge' should appear at the top right of the portrait card.
7. Use the first 20 results from all users who have any of the following user roles: classic_lifestyle, silver_lifestyle, rockstar_lifestyle, premium_lifestyle or jewel_affiliate user roles, sorted in order from newest to oldest.
 * 
 */
if (!defined('ABSPATH')) exit;

add_action('init', function() {
    add_shortcode('bz_featured_members_card', 'bz_featured_members_card');
});

function bz_featured_members_card() {
    $allowed_roles = [
        'classic_lifestyle',
        'silver_lifestyle',
        'rockstar_lifestyle',
        'premium_lifestyle',
        'jewel_affiliate'
    ];
    
    $users = get_users([
        'role__in' => $allowed_roles,
        'number'   => 20,
        'orderby'  => 'registered',
        'order'    => 'DESC',
        'fields'   => 'all'
    ]);
    
    if (empty($users)) return '<div class="bz-fm-wrap">No featured members yet.</div>';
    
    $now = current_time('timestamp');
    $cards = [];
    foreach ($users as $u) {
        $avatar = get_user_meta($u->ID,'profile_picture',true);
        if (!$avatar) $avatar = get_avatar_url($u->ID,['size'=>420]);
        if (!$avatar) $avatar = 'https://buzzjuice.net/wp-content/uploads/default-portrait.jpg';
    
        $cards[] = [
            'avatar'   => esc_url($avatar),
            'username' => esc_js($u->user_login),
            'url'      => esc_url("https://buzzjuice.net/social/@{$u->user_login}"),
            'new'      => (strtotime($u->user_registered) > ($now - 7 * DAY_IN_SECONDS))
        ];
    }
    if (empty($cards)) return '<div class="bz-fm-wrap">No featured members found.</div>';

    ob_start(); ?>
<div class="bz-fm-wrap">
  <div class="bz-fm-title">🌟 Featured Members</div>
  <div class="bz-fm-card">
    <img id="bz-fm-img" src="" alt="Profile" draggable="false">
    <div class="bz-fm-zone left"></div>
    <div class="bz-fm-zone right"></div>
    <div class="bz-fm-nav left">&#10094;</div>
    <div class="bz-fm-nav right">&#10095;</div>
    <div class="bz-fm-badge" id="bz-fm-badge">NEW</div>
    <div class="bz-fm-overlay">
      <div class="bz-fm-username" id="bz-fm-username"></div>
    </div>
  </div>
  <a href="https://buzzjuice.net/social/" class="bz-fm-btn">Explore Matches &rarr;</a>
</div>
<style>
.bz-fm-wrap{background:#fff;border:1px solid #e7eaf1;border-radius:12px;padding:10px;margin-top:16px;}
.bz-fm-title{font-size:13px;font-weight:600;color:#3E6CB8;margin-bottom:8px;}
.bz-fm-card{position:relative;width:100%;height:420px;border-radius:16px;overflow:hidden;background:#edeef1;margin-bottom:12px;align-content: center;}
.bz-fm-card img{width:100%;height:100% !important;object-fit:cover;background:#e3e3e3;display:block;cursor:pointer;}
.bz-fm-zone{position:absolute;top:0;width:50%;height:100%;z-index:2;}
.bz-fm-zone.left{left:0;}
.bz-fm-zone.right{right:0;}
.bz-fm-nav{position:absolute;top:50%;transform:translateY(-50%);width:36px;height:36px;border-radius:50%;background:rgba(0,0,0,0.36);color:#fff;display:flex;align-items:center;justify-content:center;font-size:22px;z-index:3;cursor:pointer;}
.bz-fm-nav.left{left:12px;}
.bz-fm-nav.right{right:12px;}
.bz-fm-badge{position:absolute;top:16px;right:16px;background:#ff3366;color:#fff;padding:3px 12px;border-radius:12px;font-size:11px;font-weight:700;display:none;z-index:4;}
.bz-fm-overlay{position:absolute;bottom:0;width:100%;padding:17px 12px 10px;background:linear-gradient(180deg,transparent,rgba(0,0,0,0.89));color:#fff;left:0;z-index:5;}
.bz-fm-username{font-size:15px;text-decoration:underline;cursor:pointer;}
.bz-fm-btn{display:block;text-align:center;margin-top:8px;background:#531bb9;color:#fff;padding:12px 0;border-radius:8px;text-decoration:none;font-size:15px;font-weight:600;transition:background .14s;}
.bz-fm-btn:hover{background:#1a4281;}

a.bz-fm-btn {
    color: white !important;
}

@media(max-width:700px){.bz-fm-card{height:115vw; min-height:220px;} }
@media(max-width:500px){.bz-fm-card{height:115vw; min-height:180px;} .bz-fm-btn{font-size:16px;} }
</style>
<script>
(function(){
  const cards = <?php echo json_encode(array_values($cards)); ?>;
  if(!cards.length) return;
  let idx=0;
  const img=document.getElementById('bz-fm-img');
  const username=document.getElementById('bz-fm-username');
  const badge=document.getElementById('bz-fm-badge');
  function show(i){
    let m=cards[i];
    img.src=m.avatar;
    username.textContent='@'+m.username;
    badge.style.display=m.new?'block':'none';
    img.onclick=()=>window.open(m.url,'_blank');
    username.onclick=()=>window.open(m.url,'_blank');
  }
  function next(){ idx=(idx+1)%cards.length; show(idx);}
  function prev(){ idx=(idx-1+cards.length)%cards.length; show(idx);}
  document.querySelector('.bz-fm-nav.left').onclick=prev;
  document.querySelector('.bz-fm-nav.right').onclick=next;
  document.querySelector('.bz-fm-zone.left').onclick=prev;
  document.querySelector('.bz-fm-zone.right').onclick=next;
  // Touch/Swipe event
  let startX=null;
  img.addEventListener('touchstart',e=>{startX=e.touches[0].clientX;});
  img.addEventListener('touchend',e=>{
    if(startX===null) return;
    let dx=e.changedTouches[0].clientX-startX;
    if(dx>40) prev();
    else if(dx<-40) next();
    startX=null;
  },{passive:true});
  show(0);
})();
</script>
<?php
    return ob_get_clean();
}