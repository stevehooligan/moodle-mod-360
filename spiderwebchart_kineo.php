<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
 ?>

<script type="text/javascript" src="js/swfobject.js"></script>

<script type="text/javascript">
    var flashvars = {};
    flashvars.analysisid = <?php
        /** @var int $analysisid */
        echo $analysisid
    ?>;
    flashvars.activityid = <?php echo $activityid ?>;
<?php
/** @var array $filters */
foreach ($filters as $code => $name) {
    echo "    flashvars.filter_" . $code . "=\"" . $name . "\";\n";
}
?>
    flashvars.scriptURL = "<?php
        /** @var string $scriptURL */
        echo $scriptURL
        ?>";
    swfobject.embedSWF("spiderweb.swf", "my_chart", "600", "600", "7.0.0", false, flashvars);
</script>

<div style="text-align: center;">
    <div id="my_chart"></div>
</div>
