<?php ! defined( 'ABSPATH' ) && exit(); ?><div id="totalpoll-presets" ng-app="presets" ng-controller="MainController as $ctrl">
    <div class="totalpoll-loading" ng-if="! $ctrl.initialized">
        <div class="totalpoll-loading-spinner"></div>
		<?php esc_html_e( 'Loading...', 'totalpoll' ); ?>
    </div>
    <h1 class="totalpoll-title">Bulk apply presets</h1>
    <br>
    <div class="totalpoll-row" ng-if="$ctrl.isReady()">
        <section class="totalpoll-column">
            <div class="totalpoll-column-content" ng-class="{'is-disabled' : $ctrl.isPollsDisabled() }">
                <header class="totalpoll-column-header">
                    <h2 class="totalpoll-column-title">
                        1 - <?php esc_html_e( 'Select Polls', 'totalpoll' ) ?>
                        <small ng-if="$ctrl.selectedPolls.length">{{ $ctrl.selectedPolls.length }} <?php esc_html_e('selected Poll(s)', 'totalpoll') ?></small>
                    </h2>
                </header>
                <div class="totalpoll-column-body">
                    <ul class="totalpoll-list">
                        <li class="totalpoll-list-item"
                            ng-class="{'is-active' : $ctrl.selectedPolls.includes(poll.ID)}"
                            ng-repeat="poll in $ctrl.polls"
                            ng-click="$ctrl.togglePoll(poll.ID)">
                            {{ poll.post_title }}
                        </li>
                    </ul>
                    <button class="button button-primary" ng-disabled="$ctrl.processing" ng-click="$ctrl.loadPolls($event)"><?php esc_html_e( 'Load more', 'totalpoll' ) ?></button>
                    <button class="button" ng-disabled="$ctrl.processing" ng-click="$ctrl.resetPolls()"><?php esc_html_e( 'Reset selection', 'totalpoll' ) ?></button>
                </div>
            </div>
        </section>
        <section class="totalpoll-column">
            <div class="totalpoll-column-content" ng-class="{'is-disabled' : $ctrl.isPresetDisabled() }">
                <header class="totalpoll-column-header">
                    <h2 class="totalpoll-column-title">2 - <?php esc_html_e( 'Select a preset', 'totalpoll' ) ?></h2>
                </header>
                <div class="totalpoll-column-body">
                    <ul class="totalpoll-list is-presets">
                        <li ng-repeat="preset in $ctrl.presets"
                            class="totalpoll-list-item"
                            ng-class="{'is-active' : $ctrl.selectedPreset.ID === preset.ID, 'is-soft' : preset.type === 'soft', 'is-hard' : preset.type === 'hard'}"
                            ng-click="$ctrl.selectPreset(preset)">
                            {{ preset.post_title }} <span class="totalpoll-list-label">{{ preset.type }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </section>
        <section class="totalpoll-column">
            <div class="totalpoll-column-content" ng-class="{'is-disabled' : ! $ctrl.selectedPreset }">
                <header class="totalpoll-column-header">
                    <h2 class="totalpoll-column-title">3 - <?php esc_html_e( 'Apply preset', 'totalpoll' ) ?></h2>
                </header>
                <div class="totalpoll-column-body totalpoll-presets-processing">
                    <button ng-click="$ctrl.applyPreset()" class="button button-primary button-hero" ng-disabled="$ctrl.isDisabled()">Apply Preset</button>
                    <br>
                    <p class="totalpoll-text" ng-if="$ctrl.selectedPreset">
                        <strong>{{ $ctrl.selectedPreset.post_title }}</strong> preset will be
                        applied to <strong>{{ $ctrl.selectedPolls.length }} selected poll(s)</strong> with
                        {{ $ctrl.selectedPreset.type}} merge</p>
                    <br>

                    <div ng-if="$ctrl.processing">
                        <div class="totalpoll-progress-container">
                            <div class="totalpoll-progress">
                                <div class="totalpoll-progress-bar" ng-style="{ width: $ctrl.getProgress() + '%'}"></div>
                            </div>
                            <p class="totalpoll-progress-counter">{{ $ctrl.done.length }} / {{ $ctrl.total }}</p>
                        </div>

                        <ul class="totalpoll-list">
                            <li class="totalpoll-list-item" ng-repeat="poll in $ctrl.done"
                                ng-class="{ 'is-success' : poll.success, 'has-failed' : !poll.success}">{{ poll.title }}</li>
                        </ul>
                        <button ng-if="$ctrl.getProgress() === 100" ng-click="$ctrl.reset()" class="button button-primary"><?php esc_html_e('Done', 'total') ?></button>
                    </div>
                </div>
            </div>
        </section>
    </div>
    <div class="totalpoll-presets-empty" ng-if="! $ctrl.isReady()">
        <p class="totalpoll-text" ng-if="$ctrl.polls.length == 0">No poll available</p>
        <p class="totalpoll-text" ng-if="$ctrl.presets.length == 0">No preset available</p>
        <a href="javascript:window.location.reload()" class="button button-primary"><?php esc_html_e( 'Reload page', 'totalpoll' ) ?></a>
    </div>
</div>
