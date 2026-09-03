<h1>Trade Journal Review</h1>
<div class="card">
<h2><?=e($trade['symbol'])?> — <?=e($trade['side'])?> #<?=e($trade['ticket']??$trade['id'])?></h2>
<p class="muted">Account: <?=e($trade['account_name'])?> · System: <?=e($trade['system_name']??'—')?> · Strategy: <?=e($trade['strategy_name']??'—')?> · Session: <?=e($trade['session_name']??'—')?></p>
<p><strong>P&amp;L:</strong> <?=trade_pnl($trade)===null?'—':number_format((float)trade_pnl($trade),2)?></p>
</div>
<form method="post">
<?=csrf_field()?>
<div class="card"><h2>Pre-trade review</h2><div class="grid">
<p><label>Setup</label><input name="setup" value="<?=e($journal['setup']??'')?>" placeholder="Breakout, pullback, reversal..."></p>
<p><label>Emotion before</label><input name="emotion_before" value="<?=e($journal['emotion_before']??'')?>" placeholder="Calm, confident, anxious..."></p>
<p><label>Confidence (1–5)</label><input type="number" min="1" max="5" name="confidence" value="<?=e($journal['confidence']??'')?>"></p>
</div><p><label>Market context</label><textarea name="market_context" placeholder="Trend, volatility, news, key levels..."><?=e($journal['market_context']??'')?></textarea></p>
<p><label>Trade thesis</label><textarea name="thesis" placeholder="Why did this trade make sense before entry?"><?=e($journal['thesis']??'')?></textarea></p>
<p><label>Entry reason</label><textarea name="entry_reason"><?=e($journal['entry_reason']??'')?></textarea></p></div>
<div class="card"><h2>Execution review</h2><div class="grid">
<p><label>Execution quality (1–5)</label><input type="number" min="1" max="5" name="execution_quality" value="<?=e($journal['execution_quality']??'')?>"></p>
<p><label>Discipline score (1–5)</label><input type="number" min="1" max="5" name="discipline_score" value="<?=e($journal['discipline_score']??'')?>"></p>
<p><label>Emotion after</label><input name="emotion_after" value="<?=e($journal['emotion_after']??'')?>"></p>
</div><p><label>Exit reason</label><textarea name="exit_reason"><?=e($journal['exit_reason']??'')?></textarea></p>
<p><label>Mistakes</label><textarea name="mistakes" placeholder="What did I do incorrectly?"><?=e($journal['mistakes']??'')?></textarea></p></div>
<div class="card"><h2>Learning</h2>
<p><label>What went well</label><textarea name="what_went_well"><?=e($journal['what_went_well']??'')?></textarea></p>
<p><label>Lessons learned</label><textarea name="lessons"><?=e($journal['lessons']??'')?></textarea></p>
<p><label>What to change next time</label><textarea name="what_to_change"><?=e($journal['what_to_change']??'')?></textarea></p>
<p><label>Tags</label><input name="tags" value="<?=e(implode(', ',array_column($journal['tags']??[],'name')))?>" placeholder="breakout, FOMO, A+ setup"></p>
<p><label>Reviewed at</label><input type="datetime-local" name="reviewed_at" value="<?=!empty($journal['reviewed_at'])?e(date('Y-m-d\\TH:i',strtotime($journal['reviewed_at']))):''?>"></p>
</div>
<button>Save journal review</button> <a href="/trades">Back to trades</a>
</form>
