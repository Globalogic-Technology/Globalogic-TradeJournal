<h1>Quick Entry from Broker</h1>
<p>Paste an Exness-style multiline trade detail, review it, then create the trade directly.</p>
<div class="card">
<p><label>Account</label><select id="broker-account" required><option value="">Select account</option><?php foreach(($accounts??[]) as $a):?><option value="<?=e($a['id'])?>"><?=e($a['name'])?> (<?=e($a['currency'])?>)</option><?php endforeach;?></select></p>
<textarea id="broker" rows="16" placeholder="BTC/USD\nsell\n2026-07-01 09:14:59\n2026-07-01 09:22:58\n0.01\n106811.43\n106794.80\n+0.16\nPosition ID\n1590998273\nCommission, USD\n-0.16"></textarea>
<p><button type="button" id="parse">Parse Data</button></p><div id="result"></div>
</div>
<script nonce="<?=e(csp_nonce())?>">
(function(){
const text=document.getElementById('broker'),out=document.getElementById('result'),account=document.getElementById('broker-account');
const csrf='<?=e(csrf_token())?>';
const num=x=>{const n=parseFloat(String(x||'').replace(/,/g,''));return Number.isFinite(n)?n:null};
function parse(){
 const l=text.value.trim().split(/\r?\n/).map(x=>x.trim()).filter(Boolean);
 if(l.length<8){out.innerHTML='<p class="error">Expected at least 8 non-empty lines.</p>';return;}
 const symbol=l[0].toUpperCase(),side=/sell/i.test(l[1])?'short':'long',quantity=num(l[4]),entry=num(l[5]),exit=num(l[6]);
 const pnl=num((l[7]||'').match(/[+-]?[\d,.]+(?:\.\d+)?/)?.[0]);
 const ticket=l.slice(8).find(x=>/^\d+$/.test(x))||'';
 const feeMatch=l.slice(8).join(' ').match(/(?:commission|fee)[^\d-]*(-?[\d,.]+(?:\.\d+)?)/i),fee=Math.abs(num(feeMatch&&feeMatch[1])||0);
 if(quantity===null||entry===null||exit===null){out.innerHTML='<p class="error">Quantity, entry and exit price must be numeric.</p>';return;}
 const opened=l[2].replace(' ','T'),closed=l[3].replace(' ','T');
 out.innerHTML='<div class="card"><h2>Parsed trade</h2><div class="grid"><p><strong>Symbol</strong><br>'+symbol+'</p><p><strong>Side</strong><br>'+side+'</p><p><strong>Quantity</strong><br>'+quantity+'</p><p><strong>Entry</strong><br>'+entry+'</p><p><strong>Exit</strong><br>'+exit+'</p><p><strong>P&amp;L</strong><br>'+(pnl===null?'—':pnl)+'</p><p><strong>Fee</strong><br>'+fee+'</p><p><strong>Ticket</strong><br>'+ticket+'</p></div><p><button type="button" id="create">Create Trade</button></p></div>';
 document.getElementById('create').onclick=function(){if(!account.value){alert('Select an account first.');return;}const f=document.createElement('form');f.method='post';f.action='/trades';const fields={_csrf:csrf,action:'save',account_id:account.value,symbol:symbol,side:side,status:'closed',opened_at:opened,closed_at:closed,quantity:String(quantity),entry_price:String(entry),exit_price:String(exit),fees:String(fee),ticket:ticket,notes:'Imported from broker paste'+(pnl===null?'':'; broker P&L '+pnl)};for(const k in fields){const i=document.createElement('input');i.type='hidden';i.name=k;i.value=fields[k];f.appendChild(i)}document.body.appendChild(f);f.submit();};
}
document.getElementById('parse').addEventListener('click',parse);
})();
</script>
