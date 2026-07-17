<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! isset( $mycred_ai_ranks_active ) ) {
	$mycred_ai_ranks_active = false;
}
if ( ! isset( $mycred_ai_badges_active ) ) {
	$mycred_ai_badges_active = false;
}
if ( ! isset( $mycred_ai_avatar_url ) ) {
	$mycred_ai_avatar_url = '';
}
if ( ! isset( $mycred_ai_upcoming_abilities ) || ! is_array( $mycred_ai_upcoming_abilities ) ) {
	$mycred_ai_upcoming_abilities = array();
}
?>
<div class="wrap mycred-ai-assistant-wrapper">
	<h1 class="screen-reader-text"><?php _e( 'AI Assistant (Experiment)', 'mycred' ); ?></h1>
	
	<div class="mycred-ai-assistant-container">
		
		<!-- Main Chat UI Area -->
		<div class="mycred-ai-chat-interface">
			
			<!-- Sidebar / Info Panel -->
			<div class="mycred-ai-sidebar">
				<div class="mycred-ai-brand">
					<h2><?php _e( 'AI Assistant (Experiment)', 'mycred' ); ?></h2>
				</div>
				
				<div class="mycred-ai-intro">
					<p>
						<?php
						printf(
							'%1$s <a href="%2$s" class="mycred-ai-read-more" target="_blank" rel="noopener noreferrer">%3$s</a>',
							esc_html__( 'Powered by WordPress 7.0 AI Core engine. Manage points, view stats, and run user transactions using simple natural language.', 'mycred' ),
							esc_url( 'https://mycred.me/blog/ai-assistant-for-wordpress-gamification-and-loyalty/?utm_source=wp_org&utm_medium=read_me&utm_campaign=ai-assistant' ),
							esc_html__( 'Read more', 'mycred' )
						);
						?>
					</p>
				</div>
				
				<div class="mycred-ai-sidebar-section abilities-section">
					<h3><?php _e( 'Available Abilities', 'mycred' ); ?></h3>
					<div class="abilities-chips abilities-chips--active" role="list">
						<span class="ability-chip ability-chip--balance" role="listitem" title="mycred/get-user-balance">
							<span class="ability-chip__icon dashicons dashicons-search" aria-hidden="true"></span>
							<span class="ability-chip__label"><?php _e( 'Check user balance', 'mycred' ); ?></span>
						</span>
						<span class="ability-chip ability-chip--summary" role="listitem" title="mycred/get-site-points-summary">
							<span class="ability-chip__icon dashicons dashicons-chart-bar" aria-hidden="true"></span>
							<span class="ability-chip__label"><?php _e( 'Site point summary', 'mycred' ); ?></span>
						</span>
						<span class="ability-chip ability-chip--award" role="listitem" title="mycred/award-points">
							<span class="ability-chip__icon dashicons dashicons-awards" aria-hidden="true"></span>
							<span class="ability-chip__label"><?php _e( 'Award points', 'mycred' ); ?></span>
						</span>
						<span class="ability-chip ability-chip--deduct" role="listitem" title="mycred/deduct-points">
							<span class="ability-chip__icon dashicons dashicons-money-alt" aria-hidden="true"></span>
							<span class="ability-chip__label"><?php _e( 'Deduct points', 'mycred' ); ?></span>
						</span>
						<span class="ability-chip ability-chip--hooks" role="listitem" title="mycred/suggest-hooks">
							<span class="ability-chip__icon dashicons dashicons-admin-plugins" aria-hidden="true"></span>
							<span class="ability-chip__label"><?php _e( 'Setup hooks', 'mycred' ); ?></span>
						</span>
						<span class="ability-chip ability-chip--point-type" role="listitem" title="mycred/create-point-type">
							<span class="ability-chip__icon dashicons dashicons-plus-alt" aria-hidden="true"></span>
							<span class="ability-chip__label"><?php _e( 'Create point type', 'mycred' ); ?></span>
						</span>
						<?php if ( $mycred_ai_ranks_active ) : ?>
						<span class="ability-chip ability-chip--ranks" role="listitem" title="mycred/suggest-ranks">
							<span class="ability-chip__icon dashicons dashicons-awards" aria-hidden="true"></span>
							<span class="ability-chip__label"><?php _e( 'Setup ranks', 'mycred' ); ?></span>
						</span>
						<?php endif; ?>
						<?php if ( $mycred_ai_badges_active ) : ?>
						<span class="ability-chip ability-chip--badges" role="listitem" title="mycred/suggest-badges">
							<span class="ability-chip__icon dashicons dashicons-star-filled" aria-hidden="true"></span>
							<span class="ability-chip__label"><?php _e( 'Setup badges', 'mycred' ); ?></span>
						</span>
						<?php endif; ?>
					</div>

					<?php if ( ! empty( $mycred_ai_upcoming_abilities ) ) : ?>
					<div class="mycred-ai-upcoming-block">
						<h3 class="mycred-ai-upcoming-heading"><?php _e( 'Upcoming', 'mycred' ); ?></h3>
						<p class="mycred-ai-upcoming-note"><?php _e( 'Planned abilities — coming in future releases.', 'mycred' ); ?></p>
						<div class="abilities-chips abilities-chips--upcoming" role="list" aria-label="<?php esc_attr_e( 'Upcoming abilities', 'mycred' ); ?>">
							<?php foreach ( $mycred_ai_upcoming_abilities as $upcoming ) : ?>
							<span
								class="ability-chip ability-chip--upcoming"
								role="listitem"
								title="<?php echo esc_attr( $upcoming['slug'] . ' — ' . $upcoming['hint'] ); ?>"
							>
								<span class="ability-chip__icon <?php echo esc_attr( $upcoming['icon'] ); ?>" aria-hidden="true"></span>
								<span class="ability-chip__label"><?php echo esc_html( $upcoming['label'] ); ?></span>
								<span class="ability-chip__badge"><?php _e( 'Soon', 'mycred' ); ?></span>
							</span>
							<?php endforeach; ?>
						</div>
					</div>
					<?php endif; ?>
				</div>
			</div>

			<!-- Main Chat Window -->
			<div class="mycred-ai-chat-main">
				<!-- Header -->
				<div class="mycred-ai-chat-header">
					<div class="chat-header-title">
						<h3><?php _e( 'AI Assistant (Experiment)', 'mycred' ); ?></h3>
					</div>
				</div>

				<!-- Messages Area -->
				<div class="mycred-ai-chat-messages" id="mycred-ai-chat-messages">
					<!-- Welcome Message -->
					<div class="mycred-ai-message-row assistant-row">
						<div class="message-avatar assistant-avatar" role="img" aria-label="<?php esc_attr_e( 'AI Assistant', 'mycred' ); ?>">
							<?php if ( ! empty( $mycred_ai_avatar_url ) ) : ?>
							<img src="<?php echo esc_url( $mycred_ai_avatar_url ); ?>" alt="" width="58" height="56" decoding="async" />
							<?php endif; ?>
						</div>
						<div class="message-bubble ai-bubble">
							<div class="message-content">
								<p><strong><?php _e( "Hello! I'm your AI Assistant (Experiment).", 'mycred' ); ?></strong></p>
								<p><?php _e( "How can I help you manage your points system today? Try asking things like:", 'mycred' ); ?></p>
								<ul class="suggestion-list">
									<li><a href="#" class="suggested-query">"What is the total point circulation?"</a></li>
									<li><a href="#" class="suggested-query">"Award 10 points to admin for contribution"</a></li>
									<li><a href="#" class="suggested-query">"Create a Gold Coins point type with key gold_coins"</a></li>
									<li><a href="#" class="suggested-query">"I have a BuddyBoss site, suggest hooks"</a></li>
									<?php if ( $mycred_ai_ranks_active ) : ?>
									<li><a href="#" class="suggested-query">"I have a community site — suggest a rank system"</a></li>
									<?php endif; ?>
									<?php if ( $mycred_ai_badges_active ) : ?>
									<li><a href="#" class="suggested-query">"Suggest badges for my community site"</a></li>
									<?php endif; ?>
								</ul>
							</div>
						</div>
					</div>
				</div>

				<!-- Chat Input Area -->
				<div class="mycred-ai-chat-footer">
					<form id="mycred-ai-chat-form" class="mycred-ai-chat-form">
						<div class="mycred-ai-input-wrapper">
							<textarea id="mycred-ai-chat-input" placeholder="<?php esc_attr_e( 'Ask the AI assistant...', 'mycred' ); ?>" required rows="1"></textarea>
							<button type="submit" id="mycred-ai-send-btn" title="<?php esc_attr_e( 'Send message', 'mycred' ); ?>">
								<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
									<line x1="22" y1="2" x2="11" y2="13"></line>
									<polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
								</svg>
							</button>
						</div>
					</form>
				</div>
			</div>

		</div>
	</div>
</div>
