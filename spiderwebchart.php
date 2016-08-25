<script type="text/javascript" src="js/json/json2.js"></script>
<script type="text/javascript" src="js/swfobject.js"></script>
<script type="text/javascript">
swfobject.embedSWF("open-flash-chart.swf", "my_chart", "550", "400", "9.0.0");
</script>

<script type="text/javascript">

//noinspection JSUnusedLocalSymbols
function ofc_ready()
{
//    alert('ofc_ready');
}

//noinspection JSUnusedLocalSymbols
function open_flash_chart_data()
{
    return JSON.stringify(data);
}

//noinspection JSUnusedLocalSymbols
function findSWF(movieName) {
  if (navigator.appName.indexOf("Microsoft")!= -1) {
    return window[movieName];
  } else {
    return document[movieName];
  }
}

var data = <?php
	/** @var open_flash_chart $chart */
	echo $chart->toPrettyString();
	?>;

</script>

<div style="text-align: center;">
<div id="my_chart"></div>
</div>
