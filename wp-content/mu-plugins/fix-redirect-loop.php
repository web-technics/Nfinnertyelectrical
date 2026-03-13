<?php
// Disable WordPress canonical redirect to fix redirect loop on StackCP
remove_filter('template_redirect', 'redirect_canonical');
