<?php ! defined( 'ABSPATH' ) && exit(); ?>

<div id="totalpoll-insights" class="wrap totalpoll-page" ng-app="insights">
    <h1 class="totalpoll-page-title"><?php esc_html_e( 'Insights', 'totalpoll' ); ?></h1>
    <insights-browser></insights-browser>
    <script type="text/ng-template" id="insights-browser-component-template">
        <div class="totalpoll-insights" ng-class="{'totalpoll-processing': $ctrl.isProcessing()}">
            <div class="totalpoll-box totalpoll-insights-header">
                <div class="totalpoll-insights-header-polls">
                    <select ng-model="$ctrl.filters.poll" ng-options="poll.id as poll.title for poll in $ctrl.polls" ng-change="$ctrl.loadMetrics()">
                        <option value=""><?php esc_html_e( 'Please select a poll', 'totalpoll' ); ?></option>
                    </select>
                </div>
                <div class="totalpoll-insights-header-date">
                    <span><?php esc_html_e( 'From', 'totalpoll' ); ?></span>
                    <input type="text" datetime-picker='{"timepicker":false, "mask":true, "format": "Y-m-d"}' ng-model="$ctrl.filters.from">
                    <span><?php esc_html_e( 'To', 'totalpoll' ); ?></span>
                    <input type="text" datetime-picker='{"timepicker":false, "mask":true, "format": "Y-m-d"}' ng-model="$ctrl.filters.to">
                    <div class="button-group">
                        <button class="button" ng-click="$ctrl.resetFilters()" ng-disabled="!($ctrl.filters.from || $ctrl.filters.to)">
							<?php esc_html_e( 'Clear', 'totalpoll' ); ?>
                        </button>
                        <button class="button button-primary" ng-click="$ctrl.loadMetrics()">
							<?php esc_html_e( 'Apply', 'totalpoll' ); ?>
                        </button>
                    </div>
                </div>
                <div class="totalpoll-insights-header-export" ng-if="$ctrl.filters.poll">
                    <span><?php esc_html_e( 'Download as', 'totalpoll' ); ?></span>
                    <div class="button-group">
                        <?php foreach ( $formats as $format => $label ): ?>
                            <button class="button" ng-class="{'button-primary': $ctrl.canExport()}" ng-click="$ctrl.exportAs('<?php echo esc_js( $format ); ?>')" ng-disabled="!$ctrl.canExport()"><?php echo esc_html( $label ); ?></button>
                        <?php endforeach; ?>
                        
                    </div>
                </div>
            </div>
        </div>

        <div class="totalpoll-insights-charts" ng-if="$ctrl.metrics.day">
            <div class="totalpoll-box">
                <h5 class="totalpoll-box-title"><?php esc_html_e('Summary', 'totalpoll') ?></h5>
                <div class="totalpoll-box-section">
                    <div class="totalpoll-row">
                        <div class="totalpoll-column totalpoll-column-6 totalpoll-charts" ng-repeat="(uid, question) in $ctrl.metrics.questions">
                            <h4>{{ question.content }}</h4>
                            <chart class="totalpoll-charts-item" ng-attr-options="{{ $ctrl.metrics[uid].options }}" type="doughnut" ng-model="$ctrl.metrics[uid]"></chart>
                        </div>
                    </div>
                </div>
            </div>

            <div class="totalpoll-row">
                <div class="totalpoll-column">
                    <div class="totalpoll-box">
                        <h5 class="totalpoll-box-title"><?php esc_html_e('Today', 'totalpoll') ?></h5>
                        <div class="totalpoll-box-section">
                            <chart type="line" ng-model="$ctrl.metrics.day"></chart>
                        </div>
                    </div>
                </div>
                <div class="totalpoll-column">
                    <div class="totalpoll-box">
                        <h5 class="totalpoll-box-title"><?php esc_html_e('Past month', 'totalpoll') ?></h5>
                        <div class="totalpoll-box-section">
                            <chart type="bar" ng-model="$ctrl.metrics.month"></chart>
                        </div>
                    </div>
                </div>
                <div class="totalpoll-column totalpoll-width-25">
                    <div class="totalpoll-box">
                        <h5 class="totalpoll-box-title"><?php esc_html_e('Past year', 'totalpoll') ?></h5>
                        <div class="totalpoll-box-section">
                            <chart type="bar" ng-model="$ctrl.metrics.year"></chart>
                        </div>
                    </div>
                </div>
            </div>

            <div class="totalpoll-row">
                <div class="totalpoll-column">
                    <div class="totalpoll-box">
                        <h5 class="totalpoll-box-title"><?php esc_html_e('Browsers', 'totalpoll') ?></h5>
                        <div class="totalpoll-box-section">
                            <chart type="doughnut" ng-model="$ctrl.metrics.browser"></chart>
                        </div>
                    </div>
                </div>
                <div class="totalpoll-column">
                    <div class="totalpoll-box">
                        <h5 class="totalpoll-box-title"><?php esc_html_e('OS', 'totalpoll') ?></h5>
                        <div class="totalpoll-box-section">
                            <chart type="doughnut" ng-model="$ctrl.metrics.os"></chart>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </script>
</div>



