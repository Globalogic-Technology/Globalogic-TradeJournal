<h1>Trade Journal Review</h1>
<div class="card">
<h2><?=e($trade['symbol'])?> — <?=e($trade['side'])?> #<?=e($trade['ticket']??$trade['id'])?></h2>
<p class="muted">Account: <?=e($trade['account_name'])?> · System: <?=e($trade['system_name']??'—')?> · Strategy: <?=e($trade['strategy_name']??'—')?> · Session: <?=e($trade['session_name']??'—')?></p>
<p><strong>P&amp;L:</strong> <?=trade_pnl($trade)===null?'—':number_format((float)trade_pnl($trade),2)?></p>
</div>
<form method="post">
<?=csrf_field()?>
<datalist id="setup-options">
<option value="Breakout"><option value="Pullback"><option value="Reversal"><option value="Trend continuation"><option value="Support bounce"><option value="Resistance rejection"><option value="Range breakout"><option value="Range reversal"><option value="Momentum"><option value="Mean reversion"><option value="Liquidity sweep"><option value="Retest"><option value="Other">
</datalist>
<datalist id="emotion-options">
<option value="Calm"><option value="Confident"><option value="Focused"><option value="Neutral"><option value="Cautious"><option value="Uncertain"><option value="Anxious"><option value="Fearful"><option value="Greedy"><option value="Excited"><option value="Frustrated"><option value="Angry"><option value="FOMO"><option value="Revenge trading"><option value="Other">
</datalist>
<datalist id="market-context-options">
<option value="Strong uptrend"><option value="Strong downtrend"><option value="Sideways / range"><option value="Choppy"><option value="High volatility"><option value="Low volatility"><option value="Trending with pullback"><option value="Near support"><option value="Near resistance"><option value="Breakout environment"><option value="News-driven"><option value="Post-news"><option value="Other">
</datalist>
<datalist id="entry-reason-options">
<option value="Breakout confirmation"><option value="Pullback confirmation"><option value="Support rejection"><option value="Resistance rejection"><option value="Trend continuation"><option value="Momentum confirmation"><option value="Liquidity sweep and reversal"><option value="Moving average confirmation"><option value="Price action confirmation"><option value="Other">
</datalist>
<datalist id="exit-reason-options">
<option value="Take profit"><option value="Stop loss"><option value="Trailing stop"><option value="Manual exit"><option value="Invalidated thesis"><option value="Risk management"><option value="End of session"><option value="News event"><option value="Other">
</datalist>
<datalist id="mistake-options">
<option value="Entered too early"><option value="Entered too late"><option value="Oversized position"><option value="Moved stop loss"><option value="Ignored stop loss"><option value="Moved take profit"><option value="Chased price"><option value="FOMO"><option value="Revenge trade"><option value="Overtraded"><option value="Ignored market context"><option value="Ignored trading plan"><option value="Exited too early"><option value="Exited too late"><option value="No mistake">
</datalist>
<datalist id="tag-options">
<option value="breakout"><option value="pullback"><option value="reversal"><option value="trend"><option value="range"><option value="momentum"><option value="support"><option value="resistance"><option value="FOMO"><option value="revenge"><option value="overtrade"><option value="discipline"><option value="patience"><option value="news"><option value="A+ setup"><option value="execution"><option value="risk management">
</datalist>
<div class="card"><h2>Pre-trade review</h2><div class="grid">
<p><label>Setup</label><input list="setup-options" name="setup" value="<?=e($journal['setup']??'')?>" placeholder="Select or type a setup..."><span class="upload-note">Choose a common setup or type your own.</span></p>
<p><label>Emotion before</label><input list="emotion-options" name="emotion_before" value="<?=e($journal['emotion_before']??'')?>" placeholder="Select or type an emotion..."></p>
<p><label>Confidence (1–5)</label><input type="number" min="1" max="5" name="confidence" value="<?=e($journal['confidence']??'')?>"></p>
</div><p><label>Market context</label><input list="market-context-options" name="market_context" value="<?=e($journal['market_context']??'')?>" placeholder="Select or type the market context..."></p>
<p><label>Trade thesis</label><textarea name="thesis" placeholder="Why did this trade make sense before entry?"><?=e($journal['thesis']??'')?></textarea></p>
<p><label>Entry reason</label><input list="entry-reason-options" name="entry_reason" value="<?=e($journal['entry_reason']??'')?>" placeholder="Select or type the entry reason..."></p></div>
<div class="card"><h2>Execution review</h2><div class="grid">
<p><label>Execution quality (1–5)</label><input type="number" min="1" max="5" name="execution_quality" value="<?=e($journal['execution_quality']??'')?>"></p>
<p><label>Discipline score (1–5)</label><input type="number" min="1" max="5" name="discipline_score" value="<?=e($journal['discipline_score']??'')?>"></p>
<p><label>Emotion after</label><input list="emotion-options" name="emotion_after" value="<?=e($journal['emotion_after']??'')?>" placeholder="Select or type an emotion..."></p>
</div><p><label>Exit reason</label><input list="exit-reason-options" name="exit_reason" value="<?=e($journal['exit_reason']??'')?>" placeholder="Select or type the exit reason..."></p>
<p><label>Mistakes</label><input list="mistake-options" name="mistakes" value="<?=e($journal['mistakes']??'')?>" placeholder="Select or type a mistake..."></p></div>
<div class="card"><h2>Learning</h2>
<p><label>What went well</label><textarea name="what_went_well"><?=e($journal['what_went_well']??'')?></textarea></p>
<p><label>Lessons learned</label><textarea name="lessons"><?=e($journal['lessons']??'')?></textarea></p>
<p><label>What to change next time</label><textarea name="what_to_change"><?=e($journal['what_to_change']??'')?></textarea></p>
<p><label>Tags</label><input list="tag-options" name="tags" value="<?=e(implode(', ',array_column($journal['tags']??[],'name')))?>" placeholder="Select a tag or type your own..."><span class="upload-note">You can enter multiple tags separated by commas.</span></p>
<p><label>Reviewed at</label><input type="datetime-local" name="reviewed_at" value="<?=!empty($journal['reviewed_at'])?e(date('Y-m-d\\TH:i',strtotime($journal['reviewed_at']))):''?>"></p>
</div>
<button>Save journal review</button> <a href="/trades">Back to trades</a>
</form>
