<?php
interface WMAIW_AI_Provider {
    public function generate($prompt, $params = array());
    public function is_available();
}