jQuery(function($) {
    const V = {
        init() {
            this.$c   = $('.zb-email-validator');
            this.$in  = this.$c.find('.zb-email-input');
            this.$bt  = this.$c.find('.zb-validate-btn');
            this.$st  = this.$c.find('.zb-status-value');
            this.$res = this.$c.find('.zb-validation-results');
            this.bind();
        },
        bind() {
            this.$bt.on('click', ()=> this.submit());
            this.$in.on('keypress', e=> { if(e.which===13) this.submit(); });
        },
        submit() {
            const email = this.$in.val().trim();
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                return this.error(zbEmailValidator.strings.invalid_email);
            }
            this.$st.hide();
            this.$res.hide();

            $.post(zbEmailValidator.ajax_url, {
                action:   'zb_create_validation_task',
                security: zbEmailValidator.nonce,
                email:    email
            }, resp=> {
                if (resp.success) {
                    this.show(resp.data.result);
                } else {
                    this.error(resp.data.message || zbEmailValidator.strings.error);
                }
            }, 'json').fail(()=> this.error(zbEmailValidator.strings.error));
        },
        show(d) {
            this.$res.show();
            this.$c.find('.zb-result-email').text(d.email);
            this.badge('.zb-validity-badge', d.valid, 'Valid','Invalid');
            this.badge('.zb-exists-status', d.exists,'Yes','No','Not checked');
            this.badge('.zb-disposable-status', !d.disposable,'No','Yes');
            let txt='Unknown',cls='zb-status-unknown',tip='';
            if(d.permanent_error){
                txt='Error';cls='zb-status-error';tip=d.error_category;
            } else if(d.error_category==='accept_all'){
                txt='Yes (unreliable)';cls='zb-status-warning';tip='Server accepts all';
            } else if(typeof d.accept_all==='boolean'){
                txt=d.accept_all?'Yes (unreliable)':'No';
                cls=d.accept_all?'zb-status-warning':'zb-status-yes';
                tip=d.accept_all?'Server accepts all':'';
            }
            const $aa=this.$c.find('.zb-acceptall-status');
            $aa.text(txt).attr('title',tip)
                .removeClass('zb-status-yes zb-status-no zb-status-warning zb-status-error zb-status-unknown')
                .addClass(cls);
        },
        badge(sel,flag,yes,no,unk='') {
            const $e = this.$c.find(sel).removeClass();
            if (flag===true)    return $e.text(yes).addClass('zb-status-yes');
            if (flag===false)   return $e.text(no ).addClass('zb-status-no');
            return $e.text(unk||'Unknown').addClass('zb-status-unknown');
        },
        error(msg) {
            this.$st.text(msg).css('color','#d93025').show();
        }
    };
    if ($('.zb-email-validator').length) {
        V.init();
    }
});
