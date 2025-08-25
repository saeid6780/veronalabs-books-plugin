(function(){
    var ERR_MSG = window.BookISBNValidation ? BookISBNValidation.msg_required : 'ISBN نامعتبر است';
    var AJAX_URL = window.BookISBNValidation ? BookISBNValidation.ajax_url : '';
    var NONCE    = window.BookISBNValidation ? BookISBNValidation.nonce : '';

    function $(sel,root){ return (root||document).querySelector(sel); }
    function isValidIsbn(v){
        if(!v) return false;
        var s = String(v).replace(/[-\s]/g,'');
        if (/^\d{9}[\dXx]$/.test(s)) {
            var sum=0; for (var i=0;i<9;i++) sum+=(i+1)*parseInt(s[i],10);
            var check=(s[9].toUpperCase()==='X')?10:parseInt(s[9],10);
            return ((sum+10*check)%11)===0;
        }
        if (/^\d{13}$/.test(s)) {
            var sum=0; for (var j=0;j<12;j++) sum+=parseInt(s[j],10)*(j%2?3:1);
            var check13=(10-(sum%10))%10;
            return check13===parseInt(s[12],10);
        }
        return false;
    }
    function getField(){ return document.getElementById('book_isbn_field'); }
    function getPostId(){
        var el = document.getElementById('post_ID');
        if(el && el.value) return parseInt(el.value, 10) || 0;
    }
    function showInlineError(msg){
        var box=document.getElementById('book_isbn_box');
        if(!box) return;
        var inside=box.querySelector('.inside')||box;
        if(!inside.querySelector('.book-isbn-error')){
            var p=document.createElement('p');
            p.className='book-isbn-error';
            p.textContent=msg||ERR_MSG;
            p.style.color='red';
            inside.appendChild(p);
        }
        var input=getField(); if(input){ input.style.borderColor='red'; try{ box.scrollIntoView({behavior:'smooth',block:'center'});}catch(e){} }
    }
    function clearInlineError(){
        var box=document.getElementById('book_isbn_box');
        if(!box) return;
        var inside=box.querySelector('.inside')||box;
        var old=inside.querySelector('.book-isbn-error'); if(old) old.remove();
        var input=getField(); if(input){ input.style.borderColor=''; }
    }

    // --- Ajax check ---
    function checkUniqueIsbn(value, callback){
        if(!AJAX_URL) return callback(true);
        var xhr=new XMLHttpRequest();
        var data=new FormData();
        var postId = getPostId();
        data.append('action','check_isbn_unique');
        data.append('isbn', value);
        data.append('nonce', NONCE);
        data.append('post_id', encodeURIComponent(postId||0) );
        xhr.open('POST', AJAX_URL, true);
        xhr.onreadystatechange=function(){
            if(xhr.readyState===4){
                if(xhr.status===200){
                    try{
                        var res=JSON.parse(xhr.responseText);
                        callback(res.success && res.data.unique);
                    }catch(e){ callback(false); }
                } else {
                    callback(false);
                }
            }
        };
        xhr.send(data);
    }

    // --- Guard ---
    function guard(e,done){
        var f=getField(); var val=f?f.value.trim():'';
        if(!isValidIsbn(val)){
            if(e){ e.preventDefault(); e.stopImmediatePropagation(); }
            showInlineError(ERR_MSG);
            return done?done(false):false;
        }
        // ajax check
        checkUniqueIsbn(val,function(ok){
            if(!ok){
                if(e){ e.preventDefault(); e.stopImmediatePropagation(); }
                showInlineError(window.BookISBNValidation.msg_duplicate || 'این ISBN قبلاً استفاده شده است');
                if(done) done(false);
            } else {
                clearInlineError();
                if(done) done(true);
            }
        });
        return null;
    }

    // --- Classic Editor ---
    (function classic(){
        var form=document.getElementById('post');
        function interceptSubmit(e){
            guard(e,function(ok){ if(ok){ form.submit(); } });
        }
        if(form){ form.addEventListener('submit', function(e){ interceptSubmit(e); }, true); }
        var pub=document.getElementById('publish'); if(pub){ pub.addEventListener('click', function(e){ guard(e); }, true); }
        var save=document.getElementById('save-post'); if(save){ save.addEventListener('click', function(e){ guard(e); }, true); }
        window.addEventListener('keydown', function(e){
            if ((e.ctrlKey||e.metaKey) && (e.key==='s' || e.keyCode===83)) {
                guard(e);
            }
        }, true);
        document.addEventListener('input', function(ev){
            if (ev.target && ev.target.id==='book_isbn_field') {
                var ok=isValidIsbn(ev.target.value.trim());
                if (ok) clearInlineError();
            }
        });
    })();

    // --- Gutenberg ---
    (function gutenberg(){
        if(!(window.wp && wp.data && wp.data.dispatch && wp.data.select)) return;
        var lockKey='book-info/isbn-lock';
        var editorDisp=wp.data.dispatch('core/editor');
        var editorSel =wp.data.select('core/editor');
        var notices   =wp.data.dispatch('core/notices');
        var hasTried=false;

        function valid(){ var f=getField(); var v=f?f.value.trim():''; return isValidIsbn(v); }
        function applyLock(){
            var f=getField(); var val=f?f.value.trim():'';
            if(!valid()){ try{ editorDisp.lockPostSaving(lockKey); }catch(e){} return; }
            checkUniqueIsbn(val,function(ok){
                if(ok){ try{ editorDisp.unlockPostSaving(lockKey); }catch(e){} }
                else { try{ editorDisp.lockPostSaving(lockKey); }catch(e){} }
            });
        }
        function warnNowIfInvalid(e){
            var f=getField(); var val=f?f.value.trim():'';
            if(!isValidIsbn(val)){
                if(e){ e.preventDefault(); e.stopImmediatePropagation(); }
                hasTried=true;
                notices.removeNotice('book-isbn-error');
                notices.createErrorNotice(ERR_MSG, { id:'book-isbn-error', isDismissible:true });
                showInlineError(ERR_MSG);
                return false;
            }
            checkUniqueIsbn(val,function(ok){
                if(!ok){
                    if(e){ e.preventDefault(); e.stopImmediatePropagation(); }
                    hasTried=true;
                    notices.removeNotice('book-isbn-error');
                    notices.createErrorNotice(window.BookISBNValidation.msg_duplicate || 'این ISBN قبلاً استفاده شده است', { id:'book-isbn-error', isDismissible:true });
                    showInlineError(window.BookISBNValidation.msg_duplicate || 'این ISBN قبلاً استفاده شده است');
                } else {
                    clearInlineError();
                    notices.removeNotice('book-isbn-error');
                }
            });
            return true;
        }

        document.addEventListener('input', function(ev){
            if(ev.target && ev.target.id==='book_isbn_field'){ applyLock(); }
        });
        document.addEventListener('click', function(e){
            var el=e.target;
            var btn = el.closest('button.editor-post-publish-panel__toggle, button.editor-post-publish-button, button.editor-post-publish-button__button, button.editor-post-save-draft, button.editor-post-update-button, button.components-button.is-primary');
            if(btn){ warnNowIfInvalid(e); }
        }, true);
        window.addEventListener('keydown', function(e){
            if ((e.ctrlKey||e.metaKey) && (e.key==='s' || e.keyCode===83)) { warnNowIfInvalid(e); }
        }, true);

        setTimeout(applyLock, 600);
        wp.data.subscribe(function(){});
    })();
})();
