<div class="alert success">Export complete.</div>

<div class="tg">
    <ul class="menu tabs">
       <li><a href="#log">Export Log</a></li>
       <li><a href="#xml">structure.xml </a></li>
    </ul>
    <div id="log">
        <?php foreach($log as $log_item): ?>
        	<?php if($log_item['type'] == "title") : ?>
        	<h2><?= $log_item["text"]; ?></h2>
        	<?php else : ?>
        	<div class="alert <?= $log_item['type']; ?>" style="padding-left:27px"><?= $log_item["text"]; ?></div>
        	<?php endif; ?>
        <?php endforeach; ?>
    </div>
    <div id="xml">
        <pre class="prettyprint lang-xml candy"><?= $xml ?></pre>
    </div>
</div>

<style type="text/css" media="screen">
    .com { color: #93a1a1; }
    .lit { color: #195f91; }
    .pun, .opn, .clo { color: #93a1a1; }
    .fun { color: #dc322f; }
    .str, .atv { color: #D14; }
    .kwd, .linenums .tag { color: #1e347b; }
    .typ, .atn, .dec, .var { color: teal; }
    .pln { color: #48484c; }

    .prettyprint {
      padding: 15px;
      background-color: #fff;
      border: 1px solid #e1e1e8;
      overflow:auto;
      line-height: 18px;
      text-shadow: 0 1px 0 #fff;
      margin:0;
    }
</style>

<script src="http://google-code-prettify.googlecode.com/svn/trunk/src/prettify.js" type="text/javascript" charset="utf-8"></script>
<script type="text/javascript">prettyPrint();</script>
