class ReportsForm{
    #EV;
    constructor(db) {
        this.db = db;
        this.h = $("<div class='r-box e-box' hidden><div class='r-head'><h2>Отчеты</h2><button class='e-close-button'>×</button></div>" +
            "<div style='height: 300px'><select multiple='multiple'></select></div></div>");
        //this.h.append("<button class='e-close-button'>×</button>");
        $("main").append(this.h);
        let pW = $("main").width(), eW = this.h.outerWidth();
        this.h.css('left', pW / 2 - eW / 2).css('top', '10%').draggable({containment: "parent"});
        this.h.find(".e-close-button").click(() => {
            ReportsForm.destroy(this);
        })
        this.db.R.toArray().then(a=>{
            this.r = a;
            this.h.find("select").append(this.r.map(o=>new Option(o.name,o.id,false,false))).dblclick(e=>{
                let t=e.target,d={i:t.value,n:t.text};
                let r = this.r.find(r=>r.id == d.i);
                this.#EV('report',r.value);
                ReportsForm.destroy(this);
                /*ui.confirm("Открыть отчет: "+d.n+"?").then(async a=>{
                    if(a){
                        let r = this.r.find(r=>r.id == d.i);
                        this.#EV('report',r.value);
                        ReportsForm.destroy(this);
                    }
                })*/
            }).change(e=>{
                let t=$(e.currentTarget).val();
                console.log(t);

            });
            this.h.show();
        }).then(()=>{
            $(".r-box").contextmenu({
                delegate: "option",
                autoFocus: true,
                preventContextMenuForPopup: true,
                preventSelect: true,
                taphold: true,
                menu: [{
                    title: "Открыть",
                    cmd: "open",
                    uiIcon: "ui-icon-check"
                }, {
                    title: "Изменить",
                    cmd: "edit",
                    uiIcon: "ui-icon-pencil"
                }, {
                    title: "Удалить",
                    cmd: "delete",
                    uiIcon: "ui-icon-minus"
                }],
                select: (event, u)=> {
                    let $target = u.target;
                    let value = $target.val();
                    switch (u.cmd) {
                        case "open":
                            let r = this.r.find(r=>r.id == value);
                            this.#EV('report',r.value);
                            ReportsForm.destroy(this);
                            break
                        case "edit":
                            //CLIPBOARD = "";
                            break
                        case "delete":
                            ui.confirm("Удалить отчет: "+$target.text()+"?").then(async a=>{
                                if(a){
                                    //let r = this.r.find(r=>r.id == d.i);
                                    this.#EV('delete',value);
                                }
                            })
                            break
                    }
                    //console.log("select " + ui.cmd + " on " + $target.text() + " " +value);/**/
                },
                beforeOpen: (event, ui)=> {
                    var $menu = ui.menu,
                        $target = ui.target,
                        extraData = ui.extraData; // passed when menu was opened by call to open()

                    // console.log("beforeOpen", event, ui, event.originalEvent.type);
                    let z=$(event.target).css('z-index');
                    $menu.css('z-index',z);
                }
            })
        }).catch(e=>{
            console.log(e);
        });


    }
    #creatHTML(){

    }
    on(c){
        this.#EV=c.bind(this);
    }
    static destroy(c){
        c.h.remove();
    }
}

class Accounts{
    arr=[];
    constructor(db) {
        this.db=db;
    }
    update(){
        return  this.db.S.toArray().then(S=>{
            return this.db.A.toArray(e=>{
                this.arr= e.map(e=>{
                    e.s1=S.filter(s=>e.subconto_type1===s.subconto_type);
                    e.s1.options=function (i=false){
                        return this.map(o=>new Option(o.name+(o.remove?'[del]':''),o.id,false,i?i===o.id:i));
                    }
                    e.s2=S.filter(s=> e.subconto_type2===s.subconto_type);
                    e.s2.options=function (i=false){
                        return this.map(o=>new Option(o.name+(o.remove?'[del]':''),o.id,false,i?i===o.id:i));
                    }
                    return e;
                })
                this.arr.options=function (i=false){
                    return this.map(o=>new Option(o.number+' '+o.name+(o.remove?'[del]':''),o.id,false,i?i===o.id:i));
                }
                return this;
            });
        });
    }
    get(i){
        return this.arr.find(a=>a.id===i)
    }
    delete(i){
        return this.db.A.where({id:i}).delete().then(r=>{
            return this.update();
        });
    }
    puts(a){
        return this.db.A.bulkPut(a).then(e=>{
            return this.update();
        })
    }
}
class Subcontos{
    arr=[];
    st;
    s;
    rm;
    constructor(db,remove=1) {
        this.rm=remove;
        this.db=db;
    }
    update(){
        return  this.db.ST.where('remove').anyOf(this.rm?[0,1]:[0]).toArray().then(ST=>{
            this.st=ST;
            return this.db.S.toArray(S=>{
                this.s=S;
                this.s.options=function (i=false){
                    return this.map(o=>new Option(o.name+(o.remove?'[del]':''),o.id,false,i?i===o.id:i));
                }
                this.arr= ST.map(e=>{
                    e.s=S.filter(s=>s.subconto_type===e.id);
                    e.options=function (i=false){
                        return this.s.map(o=>new Option(o.name+(o.remove?'[del]':''),o.id,false,i?i===o.id:i));
                    }
                    return e;
                })
                this.arr.options=function (i=false){
                    return this.map(o=>new Option(o.name+(o.remove?'[del]':''),o.id,false,i?i===o.id:i));
                }
                return this;
            });
        });
    }
    sync(){
        return new Promise((rs,er)=>{
            block(1)
            server.$({command:'get_account_data'},async r=>{
                let db=this.db;
                await db.db.transaction('rw',db.A,db.ST,db.S, async (d)=>{
                    await db.A.clear();
                    await db.A.bulkPut(r.accounts);
                    await db.ST.clear();
                    await db.ST.bulkPut(r.subconto_types);
                    await db.S.clear();
                    await db.S.bulkPut(r.subcontos);
                }).then(()=>{
                    this.update().then(r=>{
                        rs(r);
                    });
                }).catch(e =>{
                    er(e.message);
                })
            },e=>{er(e)},()=>{
                block(0)
            });
        })
    }
    #F(i,A){
        return A.find(a=>a.id===i)
    }
    type(i){
        return this.#F(this.#F(i,this.s).subconto_type,this.st);
    }
    get(i){
        let t=this.#F(i,this.s);
        if(!t){
            t={name:""};
        }
        //t.name=t? t.name : "";
        /*t.n=function () {
            //let n = t;
            return t.name ? t.name : ""
        }*/
        return t;
    }
    getType(i){
        let t=this.#F(i,this.st);
        if(!t){
            t={name:""};
        }
        //t.name=t ? t.name : "";
        return t;
    }
    putTypes(a){
        return this.db.ST.bulkPut(a).then(e=>{
            return this.update();
        })
    }

    putSubcontos(a){
        return this.db.S.bulkPut(a).then(e=>{
            return this.update();
        })
    }
}
class AccountingDB{
    #EV;
    constructor(s) {
        this.db=new Dexie(s);
        let T=this,B=T.db;
        B.version(2).stores({
            entries: '&id,date,remove',
            accounts:'&id,number',
            subconto_types:'&id,remove',
            subcontos:'&id',
            stores:'&key',
            csv:'++,date',
            reports:'&id'
        });
        B.on('ready',db=>{
            return db.entries.count(c=>{
                if (c === 0){
                   return this.#sync();
                }
            }).catch(e=>{
                console.log(e);
            }).then(()=>{
                this.#EV({event:"ready"},this);
            });
        });

        B.open();
        T.E=B.entries;
        T.A=B.accounts;
        T.ST=B.subconto_types;
        T.S=B.subcontos;
        T.SS=B.stores;
        T.CS=B.csv;
        T.R=B.reports;
    }

    #sync(){
        let db=this.db;
        return new Promise((r,e)=>{
            server.$({command:"get_entries"},r,e);
        }).then(d=>{
            return db.entries.bulkPut(d.entries);
        }).then(()=>{
            return new Promise((r,e)=>{
                server.$({command:'get_account_data'},r,e);
            })
        }).then(async (r)=> {
            await db.accounts.bulkPut(r.accounts);
            await db.subconto_types.bulkPut(r.subconto_types);
            await db.subcontos.bulkPut(r.subcontos);
            await db.reports.bulkPut(r.reports);
        }).catch(e=>{console.log(e)});
    }
    sync(){
        let db=this.db;
        return new Promise((r,e)=>{
            server.$({command:"sync_all"},r,e);
        }).then(async d=>{
            if(d.tables){
                let dt= DB.db.tables;
                for(let t in d.tables){
                    await db.table(t).clear();
                    await db.table(t).bulkPut(d.tables[t]).catch(e=>{
                        console.log(e);
                    })
                }
            }
            if(d.date)
                await this.AD("last",d.date);
        }).catch(e=>{console.log(e)});
    }

    on(f){
        this.#EV=f.bind(this);
    }
    async AD(k,v){
        await this.SS.put({key:k,value:v});
    }
    async GV(k,d=null){
        let v=await this.SS.get({key:k}).catch(e=>{});
        return v&&v.value?v.value:d;
    }

}
class EntryForm{
    #OC;
    #OD;
    #OS;
    constructor(o) {
        if (!o && $('#e-new').length>0)
            return;
        this.e=(o&&o.e)?o.e:{id:'new'};
        this.h= $("<form method='post' id='e-"+this.e.id+"' class='e-box' hidden></form>");
        this.h.html(this.#creatHTML()).append("<button class='e-close-button'>×</button>");
        $("main").append(this.h);
        let pW=$("main").width(),eW=this.h.outerWidth();
        this.h.css('left',pW/2-eW/2).css('top','10%').draggable({containment: "parent"}).fadeIn();
        $("#e-"+this.e.id+" .e-close-button").click(()=>{
            EntryForm.destroy(this);
        })

        this.h.data("entry",this);

    }
    onChange(c){
        this.#OC=c.bind(this);
        this.h.change(this.#OC);
        return this;
    }
    onData(c){
        this.#OD=c;
        return this;
    }
    onSubmit(c){
        this.#OS=c.bind(this);
        return this;
    }
    data(){
        this.#OD(this);
        this.h.append("<div style='display: flex;align-items: center;justify-content: space-around'><button id='e-save' type='submit' title='Обновить проводку' style='width: 100%;'>Сохранить проводку</button>&emsp;<button id='e-delete' type='submit' title='Удалить проводку'><i class='fas fa-trash-alt'></i></button></div>");
        //this.h.append("<i class='fas fa-trash-alt' title='Удалить проводку'></i>")
        //this.h.find('#e-save, #e-delete').click(this.#OS);
        this.h.submit(this.#OS);
    }
    new(){
        this.#OD(this);
        this.h.append("<div style='display: flex;align-items: center;justify-content: space-around'><button id='e-add' type='submit' title='Сохранить проводку' style='width: 100%;'>Сохранить проводку</button></div>");
        this.h.submit(this.#OS);
        //this.h.find('#e-new').click(this.#OS);
    }
    #creatHTML(){
        let h = "<div><h2>Проводка<span id='e-num'></span></h2>" +
                "<table>" +
                "<tr>" +
                    "<th><label for='e-date'>Дата</label></th>" +
                    "<th><label for='e-sum'>Сумма</label> <label for='e-currency'>и валюта</label></th>" +
                "</tr>" +
                "<tr>" +
                    "<td><input id='e-date' type='date' name='date' autofocus></td>" +
                    "<td><input id='e-sum' type='number' name='sum' min='0' max='1000000000' step='0.01' required style='width: 70%;'>" +
                        "<select id='e-currency' name='currency' style='width: 30%;'>" +
                            "<option value='₴'>₴</option>" +
                            "<option value='$'>$</option>" +
                            "<option value='€'>€</option>" +
                            "<option value='₽'>₽</option>" +
                        "</select></td>" +
                "</tr>" +
                "<tr>" +
                    "<th><label for='e-debit'>Дебет</label><label for='e-debit_s1'></label><label for='e-debit_s2'></label></th>" +
                    "<th><label for='e-credit'>Кредит</label><label for='e-credit_s1'></label><label for='e-credit_s2'></label></th>" +
                "</tr>" +
                "<tr>" +
                    "<td><select id='e-debit' name='debit' required></select><br>" +
                        "<select id='e-debit_s1' name='debit_s1' style='cursor: help;'></select><br>" +
                        "<select id='e-debit_s2' name='debit_s2' style='cursor: help;'></select>" +
                    "</td>" +
                    "<td><select id='e-credit' name='credit' required></select><br>" +
                        "<select id='e-credit_s1' name='credit_s1' style='cursor: help;'></select><br>" +
                        "<select id='e-credit_s2' name='credit_s2' style='cursor: help;'></select>" +
                    "</td>" +
                "</tr>" +
                "<tr>" +
                    "<th colspan='2'><label for='note'>Примечание</label></th>" +
                "</tr>" +
                "<tr>" +
                    "<td colspan='2'><textarea id='e-note' name='note' required></textarea></td>" +
                "</tr>" +
                "</table>" +
                "<input id='e-cmd' type='hidden' name='command'>"+
            "</div>";
        return h;
    }
    static destroy(c){
        c.h.remove();
    }
}
class BookForm{
    #OL;
    #OC;
    #OS;/* update fields account */
    #NA;
    constructor() {
        this.h= $("<form method='post' id='b-book' class='e-box' hidden></form>");
        this.h.html(this.#creatHTML()).append("<button class='e-close-button'>×</button>");
        $("main").append(this.h);
        let pW=$("main").width(),eW=this.h.outerWidth();
        this.h.css('left',pW/2-eW/2).css('top','10%').draggable({containment: "parent"}).fadeIn();
        $("#b-book .e-close-button").click(()=>{
            EntryForm.destroy(this);
        })
        this.h.data("book",this);
    }
    #creatHTML(){
        return "<div><h2>Справочник счетов</h2>" +
            "<table>" +
            "<tr><th class='flex'>Счета<div class='click'><i class='fas fa-plus-circle'></i></div></th></tr>" +
            "<tr>" +
            "<td><div style='display: flex;align-items: center'><select id='b-account' name='account' required></select></div>" +
            "<select id='b-s1' name='s1' style='cursor: help;'></select><br>" +
            "<select id='b-s2' name='s2' style='cursor: help;'></select>" +
            "</td>" +
            "</tr>" +
            "</table>" +
            "<input id='e-cmd' type='hidden' name='command'>" +
            "</div>";
    }
    #formNewAcc(){
        return "<form method='post' id='f-new-account' class='a-box' title='Создать или изменить счет'>" +
                "<fieldset>" +
                    "<label for='a-number'>Номер счета</label>" +
                    "<input required type='number' min='0' max='100' step='1' name='a-number' id='a-number' class='text ui-widget-content ui-corner-all'>" +
                    "<label for='a-name'>Название</label>" +
                    "<input required type='text' name='a-name' id='a-name' style='width: 100%' class='text ui-widget-content ui-corner-all'>" +
                    "<input id='a-new' name='a-new' type='submit' class='ui-button ui-corner-all ui-widget' title='Добавить новый счет' value='Добавить'>" +
                    "<input id='e-cmd' type='hidden' name='command'>"+
                "</fieldset>" +
            "</form>";
    }
    load(c){
        this.#OL=c.bind(this);
        return this;
    }
    reload(){
        this.#OL();
    }
    /*update fields account*/
    update(c){
        this.#OS=c.bind(this);
        return this;
    }
    change(c){
        let t=this;
        t.#OC=c.bind(t);
        t.h.change(t.#OC);
        //t.h.on('input','select',t.#OC);
        return t;
    }
    /* new or rename account*/
    newAcc(c){
        this.#NA=c.bind(this);
        return this;
    }
    open(){
        this.#OL();
        this.h.append("<div style='display: flex;align-items: center;justify-content: space-around'><button id='b-account-save' name='b-account-save' type='submit' title='Обновить счет' style='width: 100%;'>Сохранить счет</button>&emsp;<button id='b-account-delete' name='b-account-delete' type='submit' title='Удалить счет'><i class='fas fa-trash-alt'></i></button></div>");
        /* update or delete fields account*/
        this.h.submit((e)=>{
            this.#fetch(e,this.h).then(r=>{
                this.#OS(r);
            }).catch(e=>{})
        });
        /*dialog update or new account*/
        this.h.find('.fa-plus-circle').click(()=>{
            this.dialog=$(this.#formNewAcc()).dialog({autoOpen: false,height: 'auto',width: 350,modal: true,
                /*buttons: {
                    Закрыть: function(){$(this).dialog("close");}
                },*/
                close: function() {$(this).remove();}
            }).dialog("open");
            this.dialog.submit(e=>{
                /* event result update or new account */
                this.#fetch(e,this.dialog).then(r=>{
                    this.#NA(r);
                    this.dialog.dialog('close');
                }).catch(e=>{})
            })
        });
    }
    #fetch=request;
    static destroy(c){
        c.h.remove();
    }
}
class SubcontoForm{
    #OL;
    #OC;
    #OE;
    #option={autoOpen: false,height: 'auto',width: 350,modal: true,close: function() {$(this).remove();}};
    constructor() {
        this.h= $("<form method='post' id='s-book ' class='e-box s-box' hidden></form>");
        this.h.html(this.#creatHTML()).append("<button class='e-close-button'>×</button>");
        $("main").append(this.h);
        let pW=$("main").width(),eW=this.h.outerWidth();
        this.h.css('left',pW/2-eW/2).css('top','10%').draggable({containment: "parent"}).fadeIn();
        this.h.find(".e-close-button").click(()=>{
            SubcontoForm.destroy(this);
        })
        this.h.data("subconto",this);
    }
    load(c){
        this.#OL=c.bind(this);
        return this;
    }
    reload(i){
        this.#OL(i);
    }
    event(c){
        this.#OE=c.bind(this);
        return this;
    }
    open(){
        this.#OL();
        this.h.find('.s-new-type').click(e=>{
            let o=this.#option;
            o.buttons=[{
                text: "Добавить",
                click: function() {
                    $(this).find('#action').attr({title:'Добавить тип',name:'st-new'}).click();
                }
            }]
            this.dialog=$(this.#dialogType({t:'Новый Справочник'})).dialog(o).dialog("open");
            this.dialog.submit(e=>{
                /* event result new book */
                this.#fetch(e,this.dialog).then(r=>{
                    this.#OE(r);
                    this.dialog.dialog('close');
                }).catch(e=>{})/**/
            })
        });
        this.h.find('.s-new-sub').click(e=>{
            let o=this.#option;
            o.buttons=[{
                text: "Добавить",
                click: function() {
                    $(this).find('#action').attr({title:'Добавить субконто',name:'s-new'}).click();
                }
            }]
            this.dialog=$(this.#dialogType({t:'Новый Субконто'})).dialog(o).dialog("open");
            this.dialog.submit(e=>{
                let st=$('#s-type').val();
                $(this.dialog).find('div').append("<input name='st-id' value='"+st+"'>");
                /* event result new book */
                this.#fetch(e,this.dialog).then(r=>{
                    this.#OE(r);
                    this.dialog.dialog('close');
                }).catch(e=>{})/**/
            })
        });
        this.h.find('#s-sub').dblclick(e=>{
            let t=e.target,d={i:t.value,n:t.text},o=this.#option;
            o.buttons=[{
                text: "Обновить",
                click: function() {
                    $(this).find('#action').attr({title:'Обновить субконто',name:'s-update'}).click();
                }
            },{
                text: "Удалить",
                click: function() {
                    $(this).find('#action').attr({title:'Удалить субконто',name:'s-delete'}).click();
                }
            }];
            this.dialog=$(this.#dialogType(d)).dialog(o).dialog("open");
            this.dialog.submit(e=>{
                let st=$('#s-type').val();
                $(this.dialog).find('div').append("<input name='st-id' value='"+st+"'>");
                /* event result new book */
                this.#fetch(e,this.dialog).then(r=>{
                    this.#OE(r);
                    this.dialog.dialog('close');
                }).catch(e=>{})/**/
            })
        })
        this.h.find('#s-type').dblclick(e=>{
            let t=e.target,d={i:t.value,n:t.text},o=this.#option;
            o.buttons=[{
                text: "Обновить",
                click: function() {
                    $(this).find('#action').attr({title:'Обновить тип',name:'st-update'}).click();
                    //$(this).find('#st-update').click();
                }
            },{
                text: "Удалить",
                click: function() {
                    $(this).find('#action').attr({title:'Удалить тип',name:'st-delete'}).click();
                    //$(this).find('#st-delete').click();
                }
            }];
            this.dialog=$(this.#dialogType(d)).dialog(o).dialog("open");
            this.dialog.submit(e=>{
                /* event result new book */
                this.#fetch(e,this.dialog).then(r=>{
                    this.#OE(r);
                    this.dialog.dialog('close');
                }).catch(e=>{})/**/
            })
        }).change();
    }
    change(c){
        let t=this;
        t.#OC=c.bind(t);
        t.h.change(t.#OC);
        return t;
    }
    #creatHTML(){
        return "<div><h2>Справочник Субконто</h2>" +
            "<table>" +
            "<tr><th class='flex'>Справочник<div class='s-new-type click'><i class='fas fa-plus-circle'></i></div></th></tr>" +
            "<tr><td><div style='display: flex;align-items: center'><select multiple='multiple' id='s-type' name='type'></select></div></td></tr>" +
            "</table><table>"+
            "<tr><th class='flex'>Субконто<div class='s-new-sub click'><i class='fas fa-plus-circle'></i></div></th></tr>" +
            "<tr><td><div style='display: flex;align-items: center'><select multiple='multiple' id='s-sub' name='sub'></select></div></td></tr>" +
            "</table>" +
            "<input id='e-cmd' type='hidden' name='command'>" +
            "</div>";
    }
    #dialogType(d={}){
        return "<form method='post' id='st-new-type' class='a-box' title='"+(d.t?d.t:"")+"'>" +
            "<fieldset>" +
            "<label for='name'>Название</label>" +
            "<input required type='text' name='name' id='name' value='"+(d.n?d.n:"")+"' style='width: 100%' class='text ui-widget-content ui-corner-all'>" +
            "<div hidden='hidden'>" +
            "<input id='index'  type='number' name='index' value='"+(d.i?d.i:"")+"'>" +
            "<input id='action'  type='submit'>" +
            "<input id='e-cmd' value='test' name='command'>"+
            "</div>"+
            "</fieldset>" +
            "</form>";
    }
    #fetch=request;
    static destroy(c){
        c.h.remove();
    }
}
function request(e,f){
    e.preventDefault();
    e=e.originalEvent.submitter;
    return ui.confirm(e.title).then(A=>{
        if(!A)
            throw "";
        return new Promise((rs,er)=>{
            block(1);
            f.find("#e-cmd").val(e.name);
            let err=function (e){
                try{e=e.responseJSON.error.message;}catch{}finally{ui.alert(e);}
            }
            server.post(f.serialize(),r=> {
                rs(r)
            }).fail(e=>{
                err(e)
                er(e)
            }).always(()=>{
                block(0);
            });
        })
    });
}

const server={
    aj:(m, s, dt, d, S, f,a)=>{
        $.ajax(s,{type:m,dataType:dt,data:d,success:(r)=>{
                if (S)S(r);
            },
            error:(r)=>{
                if (f)f(r);
            },timeout:200000
        }).always((j,Aj)=>{
            if(a)a(j,Aj);
        });
    },
    get:(s,d,S,f,a)=>{
        server.aj("GET",s,"json",d,S,f,a);
    },
    post:(d,S,f)=>{
        s=window.location.pathname;
        //server.aj("POST",s,"text",JSON.stringify(d),S,f);
        //server.aj("POST",s,"json",d,S,f);
        return $.post(s,d,S,"json");
    },
    $:(d,S,f,a)=>{
        s=window.location.pathname;
        server.get(s,d,S,f,a);
    }
}