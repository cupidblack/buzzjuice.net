angular.module('usinReportsApp').run(['$templateCache', function($templateCache) {
  'use strict';

  $templateCache.put('views/reports/chart.html',
    "<div>\n" +
    "	<canvas width=\"400\" height=\"400\" class=\"usin-chart-box\"></canvas>\n" +
    "</div>"
  );


  $templateCache.put('views/reports/main.html',
    "<div class=\"usin-tabs\">\n" +
    "	<ul>\n" +
    "		<li ng-class=\"['usin-tab', {'usin-tab-selected': $ctrl.currentGroup == group}]\"\n" +
    "			ng-repeat=\"group in $ctrl.reportGroups\" ng-click=\"$ctrl.changeGroup(group)\">\n" +
    "			<span class=\"usin-tab-text\">{{group.name}}</span>\n" +
    "		</li>\n" +
    "\n" +
    "	<div class=\"usin-report-options\">\n" +
    "		<usin-report-toggle reports=\"$ctrl.reports\" group=\"$ctrl.currentGroup\"\n" +
    "			on-visibility-change=\"$ctrl.changeReportVisibility(report, newVisibility)\"></usin-report-toggle>\n" +
    "		<button class=\"usin-btn usin-btn-export usin-btn-export-reports\"\n" +
    "				ng-click=\"export()\" ng-disabled=\"$ctrl.isLoading()\">\n" +
    "			<span class=\"usin-icon-export\" />\n" +
    "			<md-tooltip md-direction=\"top\">{{$ctrl.strings.export}}</md-tooltip>\n" +
    "		</button>\n" +
    "	</div>\n" +
    "	</ul>\n" +
    "</div>\n" +
    "\n" +
    "<!-- report filters -->\n" +
    "<div ng-if=\"$ctrl.currentGroup.filters\"\n" +
    "		 ng-class=\"['usin-report-group-filters', {'usin-report-group-filters-focus': $ctrl.currentGroup.isAwaitingFilterSelection}]\">\n" +
    "	<div ng-repeat=\"filter in $ctrl.currentGroup.filters\" class=\"usin-report-group-filter\">\n" +
    "		<label>{{filter.name}}</label>\n" +
    "		<usin-select-field ng-model=\"$ctrl.currentGroup.appliedFilters[filter.id]\" options=\"filter.options\"\n" +
    "											 search-action=\"filter.searchAction\" ng-change=\"$ctrl.onGroupFiltersChange()\" class=\"usin-select-large\">\n" +
    "		</usin-select-field>\n" +
    "	</div>\n" +
    "</div>\n" +
    "\n" +
    "<!-- report boxes -->\n" +
    "\n" +
    "<div class=\"usin-reports\" ng-if=\"!$ctrl.currentGroup.isAwaitingFilterSelection\">\n" +
    "	<usin-report ng-repeat=\"ro in $ctrl.reports | group: $ctrl.currentGroup | filter:{visible:true}\"\n" +
    "		report-options=\"ro\" group-filters=\"$ctrl.currentGroup.appliedFilters\" class=\"usin-report-box\">\n" +
    "	</usin-report>\n" +
    "</div>\n" +
    "<div class=\"clear\"></div>\n" +
    "<div ng-if=\"$ctrl.currentGroup.info\" class=\"usin-group-info\">\n" +
    "	<p ng-bind-html=\"$ctrl.currentGroup.info\"></p>\n" +
    "</div>\n" +
    "\n" +
    "<div class=\"usin-no-reports-found notice notice-warning\" ng-if=\"!($ctrl.reports | group:$ctrl.currentGroup).length\">\n" +
    "	<p>{{$ctrl.strings.noReportsFound}}</p>\n" +
    "</div>\n"
  );


  $templateCache.put('views/reports/period-select-dialog.html',
    "<md-dialog aria-label=\"{{strings.selectPeriod}}\">\n" +
    "		<md-toolbar>\n" +
    "			<div class=\"md-toolbar-tools\">\n" +
    "				<h2>{{strings.selectPeriod}}</h2>\n" +
    "				<span flex></span>\n" +
    "				<md-button class=\"md-icon-button\" ng-click=\"closeDialog()\">\n" +
    "					<md-icon class=\"usin-icon-delete\" aria-label=\"{{strings.cancel}}\"></md-icon>\n" +
    "				</md-button>\n" +
    "			</div>\n" +
    "		</md-toolbar>\n" +
    "\n" +
    "		<md-dialog-content>\n" +
    "			<div class=\"md-dialog-content\">\n" +
    "				{{strings.between}} <usin-date-field ng-model=\"customPeriodOptions.start\"></usin-date-field>\n" +
    "				{{strings.and}} <usin-date-field ng-model=\"customPeriodOptions.end\"></usin-date-field>\n" +
    "			</div>\n" +
    "		</md-dialog-content>\n" +
    "\n" +
    "		<md-dialog-actions layout=\"row\">\n" +
    "			<button class=\"usin-btn\" ng-click=\"closeDialog()\">\n" +
    "				{{strings.cancel}}\n" +
    "			</button>\n" +
    "			<button class=\"usin-btn usin-btn-main\" ng-click=\"applyCustomPeriod()\" ng-disabled=\"!customPeriodOptions.start && !customPeriodOptions.end\">\n" +
    "				{{strings.apply}}\n" +
    "			</button>\n" +
    "		</md-dialog-actions>\n" +
    "</md-dialog>"
  );


  $templateCache.put('views/reports/report-toggle.html',
    "<div class=\"usin-report-toggle\">\n" +
    "	<md-tooltip md-direction=\"top\">{{$ctrl.strings.toggleReports}}</md-tooltip>\n" +
    "	<div ng-click=\"$ctrl.toggleMenu()\"> \n" +
    "		<span class=\"usin-reports-visible\">{{($ctrl.reports | group:$ctrl.group | filter:{visible:true}).length}}/{{($ctrl.reports | group:$ctrl.group).length}}</span>\n" +
    "		<span class=\"usin-icon-visible usin-btn-drop-down usin-reports-icon\" ng-class=\"{'usin-btn-drop-down-opened' : $ctrl.displayed === true}\"/>\n" +
    "	</div>\n" +
    "	<div class=\"usin-fields-settings usin-drop-down\" ng-show=\"$ctrl.displayed\">\n" +
    "		<ul>\n" +
    "			<li ng-repeat=\"report in $ctrl.reports | group:$ctrl.group\">\n" +
    "				<span>\n" +
    "					<md-checkbox ng-checked=\"report.visible\" ng-click=\"$ctrl.onCheckboxChange(report)\" md-no-ink=\"true\"\n" +
    "						aria-label=\"Toggle report {{report.name}}\"></md-checkbox>\n" +
    "					{{report.name}}\n" +
    "				</span>\n" +
    "			</li>\n" +
    "		</ul>\n" +
    "	</div>\n" +
    "</div>"
  );


  $templateCache.put('views/reports/report.html',
    "<div ng-class=\"['usin-report-wrap', {'usin-simple-loading': $ctrl.loading}]\">\n" +
    "	<div class=\"usin-report-header\">\n" +
    "\n" +
    "		<span class=\"usin-report-title\">{{$ctrl.reportOptions.name}}</span>\n" +
    "		<span class=\"usin-icon-info\" ng-if=\"$ctrl.reportOptions.info\">\n" +
    "			<md-tooltip md-direction=\"right\" class=\"usin-multiline-tooltip\">{{$ctrl.reportOptions.info}}</md-tooltip>\n" +
    "		</span>\n" +
    "\n" +
    "		<ui-select ng-model=\"$ctrl.filter\" ng-change=\"$ctrl.onFilterChange()\" ng-if=\"$ctrl.hasFilters()\" \n" +
    "			theme=\"select2\" search-enabled=\"{{$ctrl.shouldEnableSearch()}}\" ng-disabled=\"$ctrl.loading\">\n" +
    "			<ui-select-match>{{$ctrl.reportOptions.filters.options[$ctrl.filter]}}</ui-select-match>\n" +
    "			<ui-select-choices repeat=\"item.key as (key , item) in $ctrl.reportOptions.filters.options | filter: $select.search\" position=\"down\">\n" +
    "				<span ng-if=\"item.key != 'custom_period'\">{{item.value}}</span>\n" +
    "				<div ng-if=\"item.key == 'custom_period'\"\n" +
    "						 ng-click=\"$ctrl.onCustomPeriodClicked($event, $select)\"\n" +
    "						 class=\"usin-custom-period-select-link\">{{$ctrl.customPeriodOptions.name}}</div>\n" +
    "			</ui-select-choices>\n" +
    "		</ui-select>\n" +
    "\n" +
    "		<div class=\"clear\"></div>\n" +
    "	</div>\n" +
    "	<div class=\"usin-report-graph\">\n" +
    "		<div ng-if=\"$ctrl.supportsPagination()\">\n" +
    "			<button ng-click=\"$ctrl.loadPrevPage()\" class=\"usin-btn usin-report-period-btn usin-report-period-btn-prev\" ng-disabled=\"$ctrl.loading\"><span class=\"usin-icon-arrow-left\"></span></button>\n" +
    "			<button ng-click=\"$ctrl.loadNextPage()\" class=\"usin-btn usin-report-period-btn usin-report-period-btn-next\" ng-disabled=\"$ctrl.loading || $ctrl.page == 0\"><span class=\"usin-icon-arrow-right\"></span></button>\n" +
    "		</div>\n" +
    "		<div ng-if=\"!$ctrl.loading && !$ctrl.error && !$ctrl.notice\">\n" +
    "			<usin-chart chart-options=\"$ctrl.chartOptions\"></usin-chart>\n" +
    "		</div>\n" +
    "		<div ng-if=\"$ctrl.error\" class=\"usin-error\">\n" +
    "			{{$ctrl.error}}\n" +
    "		</div>\n" +
    "		<div ng-if=\"$ctrl.notice\" class=\"usin-notice-box\">\n" +
    "			{{$ctrl.notice}}\n" +
    "		</div>\n" +
    "	</div>\n" +
    "</div>"
  );

}]);
