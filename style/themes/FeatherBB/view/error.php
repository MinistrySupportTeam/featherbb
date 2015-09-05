<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

use FeatherBB\Utils;
use FeatherBB\Url;

// Make sure no one attempts to run this script "directly"
if (!defined('FEATHER')) {
    exit;
}

?>
<div id="msg" class="block error">
    <h2><span><?= $msg_title ?></span></h2>
    <div class="box">
        <div class="inbox">
            <p><?php echo $msg ?></p>
            <?php if (!$no_back_link) {
                echo "\t\t\t".'<p><a href="javascript: history.go(-1)">'.__('Go back').'</a></p>';
            } ?>
        </div>
    </div>
</div>
