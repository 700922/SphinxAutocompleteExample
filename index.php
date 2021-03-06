<?php

require_once 'common.php';
require_once 'functions.php';

$docs = array();
$start =0;
$offset =10;
$current = 1;
$url = '';
if (isset($_GET['query']) && trim($_GET['query']) != '') {
	$query = trim($_GET['query']);
	$indexes = 'simplecomplete';
	if(isset($_GET['start'])) {
	    $start = $_GET['start'];
	    $current = $start/$offset+1;
	}
	$stmt = $ln_sph->prepare("SELECT * FROM $indexes WHERE MATCH(:match)  LIMIT $start,$offset OPTION ranker=sph04,field_weights=(title=100,content=1)");
	$stmt->bindValue(':match', $query,PDO::PARAM_STR);
	$stmt->execute();
	$rows = $stmt->fetchAll();
	$meta = $ln_sph->query("SHOW META")->fetchAll();
	foreach($meta as $m) {
	    $meta_map[$m['Variable_name']] = $m['Value'];
	}
	$total_found = $meta_map['total_found'];
    $total = $meta_map['total'];
	$ids = array();
	$tmpdocs = array();
	if (count($rows)> 0) {
		foreach ($rows as $v) {
			$ids[] = $v['id'];
		}
		//grab the raw content from database
		$q = "SELECT id,title ,content FROM docs WHERE id IN  (" . implode(',', $ids) . ")";

		foreach($ln->query($q) as $row)
			$tmpdocs[$row['id']] = array('title' => $row['title'], 'content' => $row[content]);
		//it's important to keep the order returned by Sphinx
		foreach ($ids as $id) {
			$docs[] = $tmpdocs[$id];
		}
	}
}
?>
<?php
$title = 'Demo simple autocomplete on title';
include 'template/header.php';
?>
<div class="container">
	<ul class="nav nav-pills">
		<li class="active"><a href="index.php">Autocomplete on titles</a>
		</li>
		<li><a href="suggestcomplete.php">Autocomplete on titles + suggestion</a>
		</li>
		<li><a href="qsuggest.php">Autocomplete on titles + CALL SUGGEST</a>
		</li>
		<li><a href="suggestcompleteexcerpts.php">Autocomplete on titles +
				suggestion + excerpts</a></li>
	</ul>
	<header>
		<h1>Simple autocomplete on title</h1>
	</header>
	<div class="row">
		<div class="span9">
			<p>Autocomplete is made using star on a titles index (with infixes).</p>
			<p>Start typing in the field below</p>
			<div class="well form-search">
				<form method="GET" action="" id="search_form">
					<input type="text" class="input-large" name="query" id="suggest"
						autocomplete="off" value="<?=isset($_GET['query'])?htmlentities($_GET['query']):''?>"> <input type="submit" class="btn btn-primary"
						id="send" name="send" value="Submit">
				</form>
			</div>
		</div>
	</div>
	<p class="lead">
		<?php if(isset($total_found)):?>
		      Total found:<?=$total_found?>
		<?php endif;?>
	</p>
	<div class="row"><div class="span" style="display: none;"></div>
		<?php if (count($docs) > 0): ?>
        <div class="span9"><?php include 'template/paginator.php';?></div>
		<?php foreach ($docs as $doc): ?>
		<div class="span9">
			<div class="container">
				<h3>
					<?= $doc['title'] ?>
				</h3>
				<p>
					<?= substr(strip_tags($doc['content']), 0, 500) . '...' ?>
				</p>

			</div>
		</div>
		<?php endforeach; ?>
	    <div class="span9"><?php include 'template/paginator.php';?></div>
		<?php elseif (isset($_GET['query']) && $_GET['query'] != ''): ?>
		<p class="lead">Nothing found!</p>
		<?php endif; ?>
	</div>
	<?php 
	$ajax_url = 'ajax_suggest.php';
	include 'template/footer.php';
	?>
