<?php
// phpcs:disable PEAR.Functions.FunctionCallSignature.MultipleArguments
// phpcs:disable PEAR.Functions.FunctionCallSignature.CloseBracketLine
// phpcs:disable PEAR.Functions.FunctionCallSignature.ContentAfterOpenBracket

use GiveP2P\P2P\Controllers\CampaignController;
use GiveP2P\P2P\Controllers\FundraiserController;
use GiveP2P\P2P\Controllers\TeamController;
use GiveP2P\Routing\Route;

Route::get( 'campaign/{campaign}', [ CampaignController::class, 'campaign' ] );

Route::get( 'campaign/{campaign}/team/{team}', [ TeamController::class, 'profile' ] );
Route::get( 'campaign/{campaign}/team/{team}/update', [ TeamController::class, 'profile' ] );
Route::get( 'campaign/{campaign}/team/{team}/donate', [ TeamController::class, 'donate' ] );

Route::get( 'campaign/{campaign}/fundraiser/{user}', [ FundraiserController::class, 'profile' ] );
Route::get( 'campaign/{campaign}/fundraiser/{user}/update', [ FundraiserController::class, 'profile' ] );
Route::get( 'campaign/{campaign}/fundraiser/{user}/donate', [ FundraiserController::class, 'donate' ] );

Route::get( 'campaign/{campaign}/(.*)', [ CampaignController::class, 'campaign' ] );
