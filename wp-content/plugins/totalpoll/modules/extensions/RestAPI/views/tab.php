<?php
! defined( 'ABSPATH' ) && exit();
 if ( version_compare( $GLOBALS['wp_version'], '4.4', '>=' ) ): ?>
    <div class="totalpoll-integration-steps">
        <div class="totalpoll-integration-steps-item">
            <div class="totalpoll-integration-steps-item-number">
                <div class="totalpoll-integration-steps-item-number-circle">1</div>
            </div>
            <div class="totalpoll-integration-steps-item-content">
                <h3 class="totalpoll-h3">
					<?php esc_html_e( 'Get poll object', 'totalpoll' ); ?>
                </h3>
                <p>
					<?php esc_html_e( 'Start by getting the poll object from this URL:', 'totalpoll' ); ?>
                </p>
                <div class="totalpoll-integration-steps-item-copy">
					<?php $restGetPoll = esc_attr( get_rest_url( null, TotalPoll()->env('rest-namespace') . '/poll/' . get_the_ID() ) ); ?>
                    <input name="" type="text" readonly onfocus="this.setSelectionRange(0, this.value.length)" value="<?php echo $restGetPoll; ?>">
                    <button class="button button-primary button-large" type="button" copy-to-clipboard="<?php echo $restGetPoll; ?>">
						<?php esc_html_e( 'Copy', 'totalpoll' ); ?>
                    </button>
                </div>
            </div>
        </div>
        <div class="totalpoll-integration-steps-item">
            <div class="totalpoll-integration-steps-item-number">
                <div class="totalpoll-integration-steps-item-number-circle">2</div>
            </div>
            <div class="totalpoll-integration-steps-item-content">
                <h3 class="totalpoll-h3">
					<?php esc_html_e( 'Cast a vote', 'totalpoll' ); ?>
                </h3>
                <p>
					<?php esc_html_e( 'You can cast a vote by sending a POST request to the following URL:', 'totalpoll' ); ?>
                </p>
                <div class="totalpoll-integration-steps-item-copy">
					<?php $restPostVote = $restGetPoll . '/vote'; ?>
                    <input name="" type="text" readonly onfocus="this.setSelectionRange(0, this.value.length)" value="<?php echo $restPostVote; ?>">
                    <button class="button button-primary button-large" type="button" copy-to-clipboard="<?php echo $restPostVote; ?>">
						<?php esc_html_e( 'Copy', 'totalpoll' ); ?>
                    </button>
                </div>
                <div class="totalpoll-code-sample">
                        <pre><span>{</span><br>  <span style="color:#660e7a;" title="Main object">"totalpoll"</span><span>: {</span><span ng-if="editor.settings.fields.length"><br>    <span
                                        style="color:#660e7a;" title="Custom fields object">"fields"</span><span>: {</span><br><span>      </span><span
                                        style="color:#660e7a;pointer-events: none;user-select: none;">"</span><span
                                        style="background: #EEEEEE;border: 1px solid #cccccc;padding: 2px 4px;border-radius: 3px;color: #666666;text-transform: uppercase;font-size: 9px;margin: 0 2px;box-shadow: inset 0 1px 0 white; vertical-align: middle;pointer-events: none;user-select: none;">Field Name</span><span
                                        style="color:#660e7a;pointer-events: none;user-select: none;">"</span><span style="pointer-events: none;user-select: none;">: </span><span
                                        style="color:#008000;pointer-events: none;user-select: none;">"</span><span
                                        style="background: #EEEEEE;border: 1px solid #cccccc;padding: 2px 4px;border-radius: 3px;color: #666666;text-transform: uppercase;font-size: 9px;margin: 0 2px;box-shadow: inset 0 1px 0 white; vertical-align: middle;pointer-events: none;user-select: none;">Field Value</span><span
                                        style="color:#008000;pointer-events: none;user-select: none;">"</span><span
                                        style="pointer-events: none;user-select: none;">,</span><br><span
                                        ng-repeat="field in editor.settings.fields"><span>      </span><span style="color:#660e7a;"
                                                                                                             title="Field name">"{{field.name || 'FIELD_NAME'}}"</span><span>: </span><span
                                            style="color:#008000;" title="Field value">"FIELD_VALUE"</span><span ng-if="!$last">,</span><br></span><span
                                        style="color:#008000;">    </span><span>},</span></span><br><span>    </span><span
                                    style="color:#660e7a;" title="Choices object">"choices"</span><span>: {</span><br><span>      </span><span
                                    style="color:#660e7a;pointer-events: none;user-select: none;">"</span><span
                                    style="background: #EEEEEE;border: 1px solid #cccccc;padding: 2px 4px;border-radius: 3px;color: #666666;text-transform: uppercase;font-size: 9px;margin: 0 2px;box-shadow: inset 0 1px 0 white; vertical-align: middle;pointer-events: none;user-select: none;">Question UID</span><span
                                    style="color:#660e7a;pointer-events: none;user-select: none;">"</span><span style="pointer-events: none;user-select: none;">: [</span><span
                                    style="color:#008000;pointer-events: none;user-select: none;">"</span><span
                                    style="background: #EEEEEE;border: 1px solid #cccccc;padding: 2px 4px;border-radius: 3px;color: #666666;text-transform: uppercase;font-size: 9px;margin: 0 2px;box-shadow: inset 0 1px 0 white; vertical-align: middle;pointer-events: none;user-select: none;">Choice UID</span><span
                                    style="color:#008000;pointer-events: none;user-select: none;">"</span><span style="pointer-events: none;user-select: none;">, </span><span
                                    style="color:#008000;pointer-events: none;user-select: none;">"</span><span
                                    style="background: #EEEEEE;border: 1px solid #cccccc;padding: 2px 4px;border-radius: 3px;color: #666666;text-transform: uppercase;font-size: 9px;margin: 0 2px;box-shadow: inset 0 1px 0 white; vertical-align: middle;pointer-events: none;user-select: none;">Choice UID</span><span
                                    style="color:#008000;pointer-events: none;user-select: none;">"</span><span style="pointer-events: none;user-select: none;">]</span><span
                                    style="pointer-events: none;user-select: none;">,</span><br><span
                                    ng-repeat="question in editor.settings.questions"><span>      </span><span style="color:#660e7a;"
                                                                                                               title="Question UID">"{{question.uid}}"</span><span>: [</span><span
                                        style="color:#008000;" title="Array of choices UIDs">"CHOICE_1_UID"</span><span><span>]</span><span
                                            ng-if="!$last">,</span><br></span></span><span>    }</span><br><span>  }</span><br><span>}</span></pre>
                </div>
            </div>
        </div>
        <div class="totalpoll-integration-steps-item">
            <div class="totalpoll-integration-steps-item-number">
                <div class="totalpoll-integration-steps-item-number-circle">3</div>
            </div>
            <div class="totalpoll-integration-steps-item-content">
                <h3 class="totalpoll-h3">
					<?php esc_html_e( 'Preview', 'totalpoll' ); ?>
                </h3>
                <p>
					<?php esc_html_e( 'Open the page which you have used these API endpoints in and test poll functionality.', 'totalpoll' ); ?>
                </p>
            </div>
        </div>
    </div>
<?php
! defined( 'ABSPATH' ) && exit();
 else: ?>
    <div class="totalpoll-feature-tip"><?php esc_html_e( 'REST API are available only in WordPress 4.4', 'totalpoll' ); ?></div>
<?php
! defined( 'ABSPATH' ) && exit();
 endif; ?>
