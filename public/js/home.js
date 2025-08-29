document.addEventListener('alpine:init', () => {
    Alpine.data('cardsList', () => ({
        items: [],
        page: 1,
        hasMore: true,
        loading: false,
        error: null,
        query: '',
        get filtered(){
            if (!this.query) return this.items
            const q = this.query.toLowerCase()
            return this.items.filter(v => (v.title || '').toLowerCase().includes(q) || (v.description || '').toLowerCase().includes(q))
        },
        formatPrice(n){
            if(n == null) return '-'
            return new Intl.NumberFormat('id-ID', { style:'currency', currency:'IDR', maximumFractionDigits:0 }).format(n)
        },
        async init(){
            await this.load()
            // Optional: auto-infinite scroll
            const io = new IntersectionObserver((entries) => {
                entries.forEach(e => { if (e.isIntersecting && this.hasMore && !this.loading) this.load() })
            }, { rootMargin: '200px' })
            // sentinel
            const btn = document.querySelector('.btn') // sederhana: pakai tombol "Load more" sebagai sentinel
            if(btn) io.observe(btn)
        },
        addID(rows){
            // pastikan setiap item punya id string
            const norm = rows.map(r => ({ ...r, id: r.id ?? `${r.eventid}-${r.startdate}` }))
            const map = new Map(this.items.map(i => [String(i.id), i]));
            for (const it of norm) map.set(String(it.id), it);
            this.items = [...map.values()];
        },
        async load(){
            try{
                this.loading = true
                this.error = null
                var xhr = new XMLHttpRequest();
                var tableData = JSON.stringify({
                    "userid": "demo@demo.com",
                    "groupid": "XCYTUA",
                    "businessid": "PJLBBS",
                    "sql": "SELECT id, keybusinessgroup, keyregistered, eventgroup, eventid, eventname, eventdescription, startdate, enddate, quota, price, inclusion, imageicon_1, imageicon_2, imageicon_3, imageicon_4, imageicon_5, imageicon_6, imageicon_7, imageicon_8, imageicon_9 FROM event_schedule",
                    "order": ""
                });
                if((sessionStorage.aes_key == undefined) && (sessionStorage.hmac_key == undefined)){
                    await handshake();
                }
                const encr = await encryptReq(tableData);
                var requestBody = {
                    uniqueid: encr.iv,
                    chiper: encr.data,
                    mac: encr.mac,
                }
                xhr.open('POST', '/pyxis/query-rsa')
                xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
                xhr.setRequestHeader('X-Merseal', sessionStorage.merseal);
                xhr.setRequestHeader('Content-Type', 'application/json');
                xhr.send(JSON.stringify(requestBody));
                xhr.onreadystatechange = async function(){
                    if(xhr.readyState == XMLHttpRequest.DONE){
                        if(xhr.status === 200){
                            const res = JSON.parse(xhr.responseText);
                            const responseDecrypted = await decryptRes(res.data, encr.iv);
                            console.log('hasil raww response ', responseDecrypted)
                            this.addID(responseDecrypted);
                        }else{
                            console.log(JSON.parse(xhr.responseText));
                        }
                    }
                }.bind(this);
            } catch (e){
                this.error = e.message || 'Failed to load'
            } finally {
                this.loading = false
            }
        },
        reset(){
            this.query = ''
        },
        buy(item){
            // contoh aksi; ganti sesuai flow-mu
            alert(`Buy: ${item.title}`)
        }
    }))
})