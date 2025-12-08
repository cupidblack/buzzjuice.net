<?php
! defined( 'ABSPATH' ) && exit();

include $template->getPath( 'views/shared/header.php' );

echo $template->userContent( $poll->getWelcomeContent() );

include $template->getPath( 'views/shared/footer.php' );
