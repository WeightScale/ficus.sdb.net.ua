<?php
//include "php.php";

require_once "lib/accounting/Auth.php";
require_once "lib/accounting/Account.php";
require_once "lib/accounting/Ficus.php";
require_once "lib/accounting/Database.php";
require_once "lib/accounting/Notify.php";

use accounting\Auth;
use accounting\Entries;
use accounting\AccountData;
use accounting\Account;
use accounting\Ficus;
use accounting\FicusDatabase;
use accounting\Notify;

$Auth = new Auth($_POST);
$msg=$Auth->getMsg();

if(isset($_POST['command'])){
    $_GET=$_POST;
}
if(isset($_GET['command']) && $_GET['command'] == "linkrestore"){
    if(isset($_SESSION["linkrestore"])){
        unset($_SESSION["linkrestore"]);
        $database = new FicusDatabase();
        try {
            $database->query("SELECT `double_hash` FROM `users` WHERE `email` = ?", $_GET["email"]);
            if ($database->next()) {
                if ($database->get("double_hash") == $_GET["double_hash"]) {

                    $password = Auth::generatePassword();

                    $subject = "Временный пароль";
                    $message1 = "Ваш временный пароль";
                    $message2 = "Нажмите";
                    $message3 = "сюда";
                    $message4 = "чтобы сразу войти в свой кабинет";
                    $message5 = "Это временный пароль. Рекомендуем изменить его в своём личном кабинете.";

                    $database->query_ex("UPDATE `users` SET `double_hash` = ? WHERE `email` = ?", sha1(sha1($password)), $_GET["email"]);
                    Notify::email($_GET["email"], $subject, "<p>" . $message1 . ": <b>" . $password . "</b></p><p>" . $message2 . " <a href='" . htmlentities("https://ficus.sdb.net.ua/index.php?email=" . $_GET["email"] . "&hash=" . sha1($password)) . "'>" . $message3 . "</a>, " . $message4 . ".</p><p>" . $message5 . "</p>");
                    $msg = "Пароль отправлен на указанную почту";
                } else
                    throw new Exception("Недействительная ссылка");

            } else
                throw new Exception("Введённая почта не зарегистрирована");
            //header("Location: ".$_SERVER['PHP_SELF']);
        }catch (Exception $e){
            $msg = $e->getMessage();
        }
    }else
        $msg = "Недействительная ссылка";

}


if($Auth->isAuth() && isset($_GET['command'])){
    switch ($_GET['command']){
        case 'get_entries'://Получить проводки
            echo (new Entries($_SESSION['user_data']))->getJson();
            break;
        case 'get_account_data'://Получить справочники и отчеты
            echo (new AccountData($_SESSION['user_data']))->all()->getJson();
            break;
        case 'sync_all':
            (new Ficus($_SESSION['user_data']))->all()->echo();
            break;
        case 'e-save'://Обновить проводку
            (new Account($_SESSION['user_data']))->update($_GET)->echo();
            break;
        case 'e-delete'://Удалить проводку
            (new Account($_SESSION['user_data']))->delete($_GET)->echo();
            break;
        case 'e-news'://Получить свежии проводки
            (new Account($_SESSION['user_data']))->news($_GET)->echo();
            break;
        case 'e-add'://Добавление новая проводка
            (new Account($_SESSION['user_data']))->add($_GET)->echo();
            break;
        case 'a-new'://Добавление нового счета
            (new AccountData($_SESSION['user_data']))->newAccount($_GET)->echo();
            break;
        case 'b-account-delete':
            (new AccountData($_SESSION['user_data']))->deleteAccount($_GET)->echo();
            break;
        case 'b-account-save':
            (new AccountData($_SESSION['user_data']))->updateAccount($_GET)->echo();
            break;
        case "st-new":
            (new AccountData($_SESSION['user_data']))->type()->new()->echo();
            break;
        case "st-delete":
            (new AccountData($_SESSION['user_data']))->type()->delete()->echo();
            break;
        case 'st-update':
            (new AccountData($_SESSION['user_data']))->type()->update()->echo();
            break;
        case 's-new':
            (new AccountData($_SESSION['user_data']))->subconto()->new()->echo();
            break;
        case 's-update':
            (new AccountData($_SESSION['user_data']))->subconto()->update()->echo();
        case 'save-report':
            $database = new FicusDatabase();
            try {
                $user = $_SESSION["user_data"]->id;
                $name = $_GET["name"];
                $value = $_GET["value"];
                $database->query_ex("INSERT INTO reports(user, name, value) VALUES (?,?,?)",$user,$name,json_encode($value,JSON_UNESCAPED_UNICODE) );
                $response = $_GET;
            }catch (Exception $e){
                $response["error"] = $e->getMessage();
            }
            echo json_encode($response,JSON_UNESCAPED_UNICODE);
            break;
        case 'del-report':
            $database = new FicusDatabase();
            try {
                $id = $_GET["value"];
                $database->query_ex("DELETE FROM reports WHERE id=?", $id);
                $response = $_GET;
            }catch (Exception $e){
                $response["error"] = $e->getMessage();
            }
            echo json_encode($response,JSON_UNESCAPED_UNICODE);
            break;
    }
    exit;
}

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <title>Бухгалтерия</title>
    <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no"/>
    <link rel="icon" href="wallet-solid.svg">
    <link rel="manifest" href="manifest.json">
    <link href="lib/webdatarocks/webdatarocks.min.css" rel="stylesheet" />
    <script src="lib/webdatarocks/webdatarocks.toolbar.min.js"></script>
    <script src="lib/webdatarocks/webdatarocks.js"></script>
    <script src="lib/jquery/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="css.min.css?<?=filemtime(__DIR__."/css.min.css")?>">
    <style>
        *{
            box-sizing: border-box;
        }
        body{
            max-width: unset;
            position: relative;
        }
        nav{
            display: flex;
            justify-content: right;
            align-items: center;
        }
    </style>
    <script>
        _=(id)=>{
            return document.getElementById(id);
        }
    </script>
</head>
<body>
    <?php /*include "header.php"; */?>
        <?php if(!$Auth->isAuth()){?>
            <style>
                .auth-box{
                    position: relative;
                    max-width: 360px;
                    width: 100%;
                    border-radius: 4px;
                    border: 1px solid black;
                    /*margin: auto auto;*/
                    top: 20%;
                    left: 50%;
                    transform: translate(-50%,-20%);
                }
                form{
                    background: transparent;
                    border: none;
                }
                input,input[type="submit"],button{
                    background: white;
                    font-size: large;
                    border-radius: 4px;
                }
                input[type="submit"],button{
                    background: green;
                    color: white;
                }
                summary,form,.background-problem,input{
                    text-align: left;
                }
                .eye{
                    position: absolute;
                    margin: 10px 0 0 -25px;
                    cursor: pointer;
                }
                a > input[type="submit"]{
                    background: unset;
                    color: unset;
                    border: unset;
                }
            </style>
            <div class="auth-box">
                <form method="POST" onsubmit="return authForm(event);">
                    <label>email:<br/><input type="email" name="email" autocomplete="new-password" placeholder="Email" required style="width: 100%;"></label>
                    <label for="id-psw1">password:</label><br/>
                    <input id="id-psw1" name="password" type="password" autocomplete="new-password" placeholder="Password" style="width: 100%;"/>
                    <i onclick="showPsw('id-psw1')" class="eye fas fa-eye"></i>
                    <hr/>
                    <input id="a-cmd" type="hidden" name='command'>
                    <input id="login" type="submit" style="width: 100%;" name="submit" value="Войти">
                    <details>
                        <summary>Забыл пароль?</summary>
                        <img src="/captcha.php" title="Капча" alt="" /><br>
                        <label>Капча</label><br><input name="captcha" style="width: 100%;">
                        <a><input id="restore" type="submit" name="submit" value="востановить"></a>
                    </details>
                </form>
                <?php if(!empty($msg)){echo "<div style='text-align: center;color: red;font-weight: 700;margin: 10px auto;'>".$msg."</div>";}?>
            </div>
            <script>
                showPsw=(e)=>{
                    let x = _(e);
                    x.type=x.type==="password"?"text":"password"
                }

                authForm=(e)=>{
                    $(e.currentTarget).find("#a-cmd").val(e.submitter.id);
                    let form = new FormData(e.currentTarget);
                    if(form.get('password')==="" && e.submitter.id==="login"){
                        alert("Введите пароль");
                        return false;
                    }
                    if(form.get('captcha')==="" && e.submitter.id==="restore"){
                        alert("Введите капчу");
                        return false;
                    }
                    return true;
                }

                /*$(()=>{
                    $(".auth-box > form").submit(e=>{
                        e.preventDefault();
                        s=e.originalEvent.submitter;
                        f=e.currentTarget;
                        $(f).find("#a-cmd").val(s.id).submit();
                        / *o = $(f).serialize();
                        block(1);
                        server.post(this.h.serialize(),r=> {
                            if(r.entries){
                                DB.E.bulkPut(r.entries).then(e=>{
                                    let F=$('#e-'+r.id).data().entry;
                                    EntryForm.destroy(F);
                                    Entries.U(DB);
                                })
                            }
                        }).fail((e)=>{
                            console.log(e.responseJSON);
                        }).always(()=>{
                            block(0);
                        });* /
                });
                })*/
            </script>
        <?php }else{?>
            <script src="/lib/jquery/jquery-3.6.0.min.js"></script>
            <link rel="stylesheet" href="/lib/jquery-ui/jquery-ui.css">
            <script src="/lib/jquery-ui/jquery-ui.js"></script>
            <script src="lib/jquery.ui-contextmenu.min.js"></script>
            <link rel="stylesheet" href="/lib/data_tables/datatables.custom.min.css?<?=filemtime(__DIR__."/lib/data_tables/datatables.custom.min.css")?>">
            <!--<link rel="stylesheet" href="https://cdn.datatables.net/1.13.3/css/jquery.dataTables.min.css">-->
            <script src="/lib/data_tables/datatables.min.js"></script>
            <script src="/lib/dexie/dexie.min.js"></script>
            <script src="/lib/accounting/entry.min.js"></script>
            <link rel="stylesheet" href="/lib/accounting/entry.min.css">
            <style>
                body{
                    max-width: unset;
                }
                nav{
                    overflow: unset;
                }
                .form-out{
                    background: unset;
                    width: fit-content;
                    min-width: unset;
                    padding: 0 10px 0 0;
                    border: unset;
                }
                table.dataTable tbody tr:hover{
                    cursor: pointer;
                    background-color: lightgrey;
                }
                nav .fas{
                    color: lightgrey;
                    font-size: 1.8em;
                }
                nav div{
                    margin: 0 10px;
                }
                .fas:hover{
                    cursor: pointer;
                    color: #6879ea;
                }
                table.dataTable .parent:before {
                    height: 1em;
                    width: 1em;
                    margin-top: -9px;
                    display: inline-block;
                    color: white;
                    border: 0.15em solid white;
                    border-radius: 1em;
                    box-shadow: 0 0 0.2em #444;
                    box-sizing: content-box;
                    text-align: center;
                    text-indent: 0 !important;
                    font-family: "Courier New", Courier, monospace;
                    line-height: 1em;
                    content: "+";
                    background-color: #31b131;
                }
                table.dataTable tr.dt-hasChild .parent:before {
                    content: "-";
                    background-color: #d33333;
                }
                /*.dataTables_wrapper{
                    width: fit-content;
                }*/
                .t-remove{
                    width: fit-content;
                    font-size: small;
                    background: white;
                    border: 1px solid;
                    margin: 0 5px;
                    /*border-radius: 3px;*/
                }
                .t-remove th{
                    background: unset;
                    color: black;
                }

                .acc-table-box>div{
                    padding-bottom: 20px;
                }
                .acc-table-box{
                    position: absolute;
                    padding: 10px;
                    margin: 0 auto;
                    z-index: 12;
                    background: #fff;
                    border: 1px solid gray;
                    box-shadow: 2px 2px 5px gray;
                    cursor: grab;
                }
                .ui-dialog-content > form{
                    text-align: left;
                    min-width: unset;
                    border: unset;
                    padding: unset;
                }
            </style>
            <script>
                let Entries,Acc,Sub,pivot,DB,BOX={},url_csv;
                let report_default={
                    "dataSource": {
                        "dataSourceType": "csv",
                        "filename": ""
                    },
                    "slice": {
                        "reportFilters": [
                            {
                                "uniqueName": "КТ"
                            },
                            {
                                "uniqueName": "ДТ"
                            },
                            {
                                "uniqueName": "Тип"
                            }
                        ],
                        "rows": [
                            {
                                "uniqueName": "ВидСубконто"
                            },
                            {
                                "uniqueName": "Субконто"
                            },
                            {
                                "uniqueName": "Дата"
                            },
                            {
                                "uniqueName": "Заметка"
                            },
                            {
                                "uniqueName": "ИД"
                            }
                        ],
                        "columns": [
                            {
                                "uniqueName": "Measures"
                            }
                        ],
                        "measures": [
                            {
                                "uniqueName": "Сумма",
                                "aggregation": "sum",
                                "format": "5de4iv9j"
                            }
                        ],
                        "expands": {
                            "rows": [
                                {
                                    "tuple": [
                                        "ВидСубконто.Сотрудники"
                                    ]
                                },
                                {
                                    "tuple": [
                                        "ВидСубконто.Сотрудники",
                                        "Субконто.К.В."
                                    ]
                                },
                                {
                                    "tuple": [
                                        "ВидСубконто.Сотрудники",
                                        "Субконто.К.В.",
                                        "Дата.2023"
                                    ]
                                }
                            ]
                        }
                    },
                    "options": {
                        "grid": {
                            "type": "classic",
                            "showTotals": "off",
                            "showGrandTotals": "off"
                        }
                    },
                    "conditions": [
                        {
                            "formula": "#value < 0",
                            "format": {
                                "backgroundColor": "#D32F2F",
                                "color": "#FFFFFF",
                                "fontFamily": "Arial",
                                "fontSize": "14px"
                            }
                        },
                        {
                            "formula": "#value > 0",
                            "format": {
                                "backgroundColor": "#0288D1",
                                "color": "#FFFFFF",
                                "fontFamily": "Arial",
                                "fontSize": "14px"
                            }
                        }
                    ],
                    "formats": [
                        {
                            "name": "5de4iv9j",
                            "thousandsSeparator": " ",
                            "decimalSeparator": ".",
                            "decimalPlaces": 2,
                            "currencySymbol": "",
                            "currencySymbolAlign": "left",
                            "nullValue": "",
                            "textAlign": "right",
                            "isPercent": false
                        }
                    ],
                    "tableSizes": {
                        "columns": [
                            {
                                "idx": 0,
                                "width": 127
                            },
                            {
                                "idx": 2,
                                "width": 113
                            }
                        ]
                    }
                };
                block=(b)=> {
                    $("#loader").css("display", b ? "block" : "none");
                }
                const ui = {
                    confirm: (m,t='') => {
                        return new Promise((rs, rj)=>{
                            $('<div></div>').dialog({
                                modal: true, title: t,
                                open: function (){$(this).html(m);},
                                buttons: {
                                    Да: function (){$(this).dialog("close");rs(1);},
                                    Нет: function (){$(this).dialog("close");rs(0);}
                                }
                            });
                        });
                    },
                    alert: (m,t='Alert') => {
                        return new Promise((rs, rj)=> {
                            $('<div></div>').dialog({
                                modal: true, title: t,
                                open: function (){$(this).html(m);},
                                buttons: {
                                    Закрыть: function (){$(this).dialog("close");rs(1);}
                                },
                                close: function (){$(this).dialog("close");rs(1);}
                            });
                        });
                    }
                }
                async function submitForm(e){
                    e.preventDefault();
                    e=e.originalEvent.submitter;
                    this.h.find("#e-cmd").val(e.id);
                    if(await ui.confirm(e.title)){
                        block(1);
                        server.post(this.h.serialize(),r=> {
                            if(r.entries){
                                DB.E.bulkPut(r.entries).then(e=>{
                                    let F=$('#e-'+r.id).data().entry;
                                    EntryForm.destroy(F);
                                    Entries.U(DB);
                                })
                            }
                        }).fail((e)=>{
                            console.log(e.responseJSON);
                        }).always(()=>{
                            block(0);
                        });
                    }
                }
                creatCSV=async ()=>{
                    let Sub=new Subcontos(DB);
                    await Sub.update();
                    await DB.db.transaction('rw', DB.CS,DB.E, async ()=>{
                        await DB.CS.clear();
                        let F=(id,A)=>{
                            return A.find(a=>a.id===id)
                        }
                        await DB.E.where({remove:0}).each(async e=>{
                            let D=Acc.get(e.debit),C=Acc.get(e.credit),k1=e,k2=e;
                            e.sum=parseFloat(e.sum);
                            k1.at='Кредит';
                            k1.vs=Sub.type(k1.credit_subconto1).name;
                            k1.s=k1.credit_subconto1?Sub.get(k1.credit_subconto1).name:"";
                            k1.D=D.number;
                            k1.K=C.number;
                            await DB.CS.put(k1);

                            if(k2.credit_subconto2){
                                k2.at='Кредит';
                                k2.vs=Sub.type(k2.credit_subconto2).name;
                                k2.s=Sub.get(k2.credit_subconto2).name;
                                k2.D=D.number;
                                k2.K=C.number;
                                await DB.CS.put(k2);
                            }

                            e.sum=-e.sum;
                            e.counts=-e.counts;
                            k1=e;
                            k1.at='Дебет';
                            k1.vs=Sub.type(k1.debit_subconto1).name;
                            k1.s=Sub.get(k1.debit_subconto1).name;
                            k1.D=C.number;
                            k1.K=D.number;
                            await DB.CS.put(k1);

                            k2=e
                            if(k2.debit_subconto2){
                                k2.at='Дебет';
                                k2.vs=Sub.type(k2.debit_subconto2).name;
                                k2.s=Sub.get(k2.debit_subconto2).name;
                                k2.D=C.number;
                                k2.K=D.number;
                                await DB.CS.put(k2);
                            }
                        })
                    })
                    return DB.CS.toArray().then(e=>{
                        let csv = [['Тип','ВидСубконто','Субконто','-Сумма','-Кол','D+Дата',"дата",'+ДТ'/*,'ДТТСК1','ДТТСК2','ДТСК1','ДТСК2'*/,'+КТ'/*,'КТТСК1','КТТСК2','КТСК1','КТСК2'*/,'Валюта','Заметка','+ИД'],...e.map(i => [
                            i.at,
                            i.vs,
                            i.s,
                            i.sum,
                            i.counts,
                            i.date,
                            i.date,
                            i.D,
                            i.K,
                            i.currency,
                            i.note,
                            i.id
                        ])
                        ].map(e => e.join(","))
                            .join("\n");
                        url_csv= window.URL.createObjectURL(new File([csv],'csv.csv',{type:'text/csv'}));
                        report_default.dataSource.filename=url_csv;
                        return url_csv;
                    })
                }
                newUpdate=(t,c)=>{
                    t.GV("last",'2000-01-01 00:00:00').then(d=>{
                        block(1);
                        fetch('/accountant.php?'+ new URLSearchParams({command:'e-news',date:d}).toString(),{
                            headers: {'Content-Type': 'application/json;charset=utf-8'}
                        }).then(r=>{
                            if(r.ok)
                                return r.json();
                            throw new Error(r.statusText);
                        }).then(async r => {
                            if (r.entries) {
                                await t.db.transaction('rw', t.E, async () => {
                                    await t.E.bulkPut(r.entries);
                                })
                            }
                            if (r.reports) {
                                await t.db.transaction('rw', t.R, async () => {
                                    await t.R.bulkPut(r.reports);
                                })
                            }
                            if (r.date)
                                await t.AD("last", r.date);
                        }).then(async r => {
                            Acc = new Accounts(DB);
                            await Acc.update();
                            Sub = new Subcontos(DB);
                            await Sub.update();
                            Entries.U(t);
                            block(0);
                            if (c) c();
                        }).catch(e=>{
                            console.error(e);
                        })
                        /*server.$({command:'e-news',date:d},async r=>{
                            if(r.entries){
                                await t.db.transaction('rw',t.E,t.SS,async ()=>{
                                    await t.E.bulkPut(r.entries);
                                })
                            }
                            if(r.reports){
                                await t.db.transaction('rw',t.R,async ()=>{
                                    await t.R.bulkPut(r.reports);
                                })
                            }
                            if(r.date)
                                await t.AD("last",r.date);
                        },e=>{
                            console.log(e);
                        },async ()=>{
                            Acc =new Accounts(DB);
                            await Acc.update();
                            Sub=new Subcontos(DB);
                            await Sub.update();
                            Entries.U(t);
                            block(0);
                            if(c)c();
                        })*/
                    });
                }
                $(()=>{
                    DB=new AccountingDB('<?= sha1($_SESSION['user_data']->id)?>');
                    DB.on(async (e,t)=>{
                        newUpdate(t,()=>creatCSV().then(f=>{
                            DB.GV("default").then(d=>{
                                if(d){
                                    d.dataSource.filename=f;
                                    pivot.setReport(d);
                                }else
                                    pivot.setReport(report_default);
                            })
                        }));
                    })
                    {let pW=$("main").width(),T=$('.acc-table-box'),eW=T.outerWidth(),H=T.height();
                        T.css('left',pW/2-eW/2).css('top','10%').resizable({
                            minWidth: 820,minHeight:H,maxHeight:H
                        }).draggable({containment: "parent"});}
                    Entries = $("#entries_table").DataTable({
                        columns: [
                            { title: "id" },
                            { title: "Дата" },
                            { title: "Дт" },
                            { title: "Субконто Дт" },
                            { title: "Кт" },
                            { title: "Субконто Кт" },
                            { title: "Сумма" },
                            { title: "Другое" },
                            { title: "D" }
                            /*{ className: 'dt-control',
                                orderable: false,
                                data: null,
                                defaultContent: '',
                            }*/
                        ],
                        language: {
                            url: "../lib/data_tables/data_tables_ru.json"
                        },
                        lengthMenu: [[10], ["10"]],
                        pageLength: 10,
                        order: [[ 0, "desc" ]]
                    });
                    Entries.on('init.dt',()=>{
                        let W=$("#entries_table_wrapper"),from=new Date();
                        from.setDate(from.getDate() -30);
                        W.prepend("<div style='float: right'><input id='from' type='date'><input id='to' type='date'><button>OK</button></div>")
                            .find('#from').val(from.toISOString().slice(0, 10));
                        W.find('#to').val(new Date().toISOString().slice(0, 10));
                        W.find('#to').next("button").click(e=>{
                            Entries.U(DB);
                        });
                    }).on('dblclick','tbody tr',(e)=>{
                        let r=Entries.row(e.currentTarget).data();
                        DB.E.get({id:r[0]}).then(r=>{
                            new EntryForm({e:r}).onData(t=>{
                                let e=t.e, h=t.h,S=Sub.s,d=Acc.get(e.debit),c=Acc.get(e.credit);
                                h.append("<input type='hidden' name='id' value='"+e.id+"'>")
                                h.find('#e-num').html(' №'+e.id);
                                h.find('#e-date').val(e.date);
                                h.find('#e-sum').val(e.sum);

                                h.find('#e-debit').append(Acc.arr.options(e.debit));
                                h.find('#e-debit_s1').append(d.s1.options(e.debit_subconto1));
                                h.find('#e-debit_s2').append(e.debit_subconto2?d.s2.options(e.debit_subconto2):new Option());
                                //h.find('#e-debit_s1').append(S.options(e.debit_subconto1));
                                //h.find('#e-debit_s2').append(e.debit_subconto2?S.options(e.debit_subconto2):new Option());

                                h.find('#e-credit').append(Acc.arr.options(e.credit));
                                h.find('#e-credit_s1').append(c.s1.options(e.credit_subconto1));
                                h.find('#e-credit_s2').append(e.credit_subconto2?c.s2.options(e.credit_subconto2):new Option());
                                //h.find('#e-credit_s1').append(S.options(e.credit_subconto1));
                                //h.find('#e-credit_s2').append(e.credit_subconto2?S.options(e.credit_subconto2):new Option());

                                h.find('#e-note').html(e.note);
                            }).onChange(function (e){
                                let t=e.target.id,v=parseInt($(e.target).val()),a=Acc.get(v);
                                if(t==="e-debit"){
                                    this.h.find('#e-debit_s1').empty().append(a.s1.options());
                                    this.h.find('#e-debit_s2').empty().append(a.subconto_type2?a.s2.options():new Option());
                                }else if(t==="e-credit"){
                                    this.h.find('#e-credit_s1').empty().append(a.s1.options());
                                    this.h.find('#e-credit_s2').empty().append(a.subconto_type2?a.s2.options():new Option());
                                }
                            }).onSubmit(submitForm).data();
                        })
                    }).on('click', 'td .parent',async function () {
                        var tr = $(this).closest('tr');
                        var row = Entries.row(tr);
                        if (row.child.isShown()) {
                            row.child.hide();
                            tr.removeClass('shown');
                        } else {
                            let arr=[];
                            let P = await DB.E.get(row.data()[0]);
                            let id=P.parent;
                            while (true){
                                let p = await DB.E.get(id);
                                arr.push(p);
                                if(p.parent){
                                    id=p.parent;
                                    continue;
                                }
                                break;
                            }
                            let arr1 = Entries.Rows(arr);
                            let html="<tr><th>id</th><th>Дата</th><th>Дт</th><th>Субконто Дт</th><th>Кр</th><th>Субконто Кт</th><th>Сумма</th><th>Описание</th></tr>";
                            arr1.forEach(e=>{
                                html+="<tr><td>"+e[0]+"</td><td>"+e[1]+"</td><td>"+e[2]+"</td><td>"+e[3]+"</td><td>"+e[4]+"</td><td>"+e[5]+"</td><td>"+e[6]+"</td><td>"+e[7]+"</td></tr>";
                            })
                            row.child("<div class='t-remove'><table >"+html+"</table></div>").show();
                            tr.addClass('shown');
                        }
                    }).on('draw', function () {
                        let T=$('.acc-table-box'),H=T.height(),o="option";
                        T.resizable(o,"minHeight",H).resizable(o,"maxHeight",H);
                    });
                    Entries.Rows=(A)=>{
                        return A.map(e => {
                            let D = Acc.get(e.debit), C = Acc.get(e.credit), ds1 = e.debit_subconto1,
                                ds2 = e.debit_subconto2, cs1 = e.credit_subconto1, cs2 = e.credit_subconto2,
                                n = "Нет вида субконто";
                            return [
                                e.id,
                                e.date,
                                D.number,
                                (ds1 ? "<span title='" + (D.subconto_type1 ? Sub.getType(D.subconto_type1).name : n + " 1") + "' style='cursor: help;'>" + Sub.get(ds1).name + "</span>" : "---") + "<br>" +
                                (ds2 ? "<span title='" + (D.subconto_type2 ? Sub.getType(D.subconto_type2).name : n + " 2") + "' style='cursor: help;'>" + Sub.get(ds2).name + "</span>" : "---"),
                                C.number,
                                (cs1 ? "<span title='" + (C.subconto_type1 ? Sub.getType(C.subconto_type1).name : n + " 1") + "' style='cursor: help;'>" + Sub.get(cs1).name + "</span>" : "---") + "<br>" +
                                (cs2 ? "<span title='" + (C.subconto_type2 ? Sub.getType(C.subconto_type2).name : n + " 2") + "' style='cursor: help;'>" + Sub.get(cs2).name + "</span>" : "---"),
                                e.sum + " " + e.currency,
                                e.note ? "<div >" + e.note + "</div>" : "",
                                e.parent ? "<i class='fas parent'></i>" : ""
                            ];
                        });
                    }
                    Entries.U=(t)=>{
                        let from=$('#entries_table_wrapper #from').val(),to=$('#entries_table_wrapper #to').val();
                        block(1)
                        t.db.transaction('r',t.E,t.A,t.ST,t.S,async ()=>{
                            let E = await t.E.where("date").between(from,to,true,true).toArray();
                            E=E.filter(r=>r.remove===0);
                            return [E,[],[],[]];
                        }).then(async (A)=>{
                            /*let rows=A[0].map(e=>{
                                let D=Acc.get(e.debit),C=Acc.get(e.credit),ds1=e.debit_subconto1,ds2=e.debit_subconto2,cs1=e.credit_subconto1,cs2=e.credit_subconto2,n="Нет вида субконто";
                                return [
                                    e.id,
                                    e.date,
                                    D.number,
                                    (ds1? "<span title='" + (D.subconto_type1 ? Sub.getType(D.subconto_type1).name : n+" 1") + "' style='cursor: help;'>" + Sub.get(ds1).name + "</span>" : "---") + "<br>" +
                                    (ds2? "<span title='" + (D.subconto_type2 ? Sub.getType(D.subconto_type2).name : n+" 2") + "' style='cursor: help;'>" + Sub.get(ds2).name + "</span>" : "---"),
                                    C.number,
                                    (cs1? "<span title='" + (C.subconto_type1 ? Sub.getType(C.subconto_type1).name : n+" 1") + "' style='cursor: help;'>" + Sub.get(cs1).name + "</span>" : "---") + "<br>" +
                                    (cs2? "<span title='" + (C.subconto_type2 ? Sub.getType(C.subconto_type2).name : n+" 2") + "' style='cursor: help;'>" + Sub.get(cs2).name + "</span>" : "---"),
                                    e.sum + " " + e.currency,
                                    e.note ? "<i title='" + e.note + "' style='cursor:help;' class='fas fa-info-circle'></i>" : "",
                                    e.parent?"<i class='fas parent'></i>":""
                                ];
                            })*/
                            let rows=Entries.Rows(A[0]);
                            Entries.clear();
                            Entries.rows.add(rows);
                            Entries.draw();
                        }).finally(()=>{
                            block(0)
                        });
                    }
                    pivot = new WebDataRocks({
                        container: "#pivot_container",
                        width: "100%",
                        height: "100%",
                        beforetoolbarcreated: (t)=>{
                            let tabs = t.getTabs();
                            t.getTabs = function() {
                                delete tabs[0];
                                delete tabs[1];
                                tabs.unshift({
                                    id: "wdr-tab-update",
                                    title: "Обновить",
                                    handler: ()=>{
                                        newUpdate(DB,()=> {
                                            creatCSV().then(U=>{
                                                this.pivot.updateData({
                                                    type: 'csv',
                                                    filename: U

                                                });
                                            });
                                        });
                                    },
                                    icon: this.icons.connect
                                }, {
                                    id: "wdr-tab-report",
                                    title: "Открыть",
                                    handler: (e)=>{
                                        $("<input type='file'>").change(e=>{
                                            let F=e.target.files[0],R=new FileReader();
                                            R.readAsText(F);
                                            R.onload=E=>{
                                                let r=JSON.parse(E.target.result);
                                                r.dataSource.filename = url_csv;
                                                pivot.setReport(r);
                                            }
                                        }).click();
                                    },
                                    icon: this.icons.open_local
                                });
                                return tabs;
                            };
                        },
                        toolbar: true,
                        global: {
                            localization: "/lib/webdatarocks/ru.json"
                        }
                    });
                    pivot.on('reportchange', function() {
                        DB.AD("default",pivot.getReport());
                    });
                    $('.new_entry').click(e=>{
                        new EntryForm().onData(t=>{
                            let h=t.h;
                            h.append("<input type='hidden' name='id' value='new'>")
                            h.find('#e-date').val(new Date().toISOString().slice(0, 10));
                            h.find('#e-debit').append(new Option()).append(Acc.arr.options());
                            h.find('#e-credit').append(new Option()).append(Acc.arr.options());
                        }).onChange(function (e){
                            let t=e.target.id,v=parseInt($(e.target).val()),a=Acc.get(v);
                            if(t==="e-debit"){
                                this.h.find('#e-debit_s1').empty().append(a?a.s1.options():'');
                                this.h.find('#e-debit_s2').empty().append(a.s2.length?a.s2.options():new Option());
                            }else if(t==="e-credit"){
                                this.h.find('#e-credit_s1').empty().append(a?a.s1.options():'');
                                this.h.find('#e-credit_s2').empty().append(a.s2.length?a.s2.options():new Option());
                            }
                        }).onSubmit(submitForm).new();
                    })
                    $('.a-acc-book').click(e=>{
                        new BookForm().load(function (){
                            let h=this.h;
                            h[0].reset();
                            h.find('#b-account,#b-s1,#b-s2').empty()
                            h.find('#b-account').append(new Option()).append(Acc.arr.options());
                        }).change(async function(e){
                            let t=e.target.id,v=parseInt($(e.target).val()),a=Acc.get(v);
                            if(t==="b-account"){
                                this.h.find('#b-s1').empty().append(new Option()).append(a?Sub.arr.options(a.subconto_type1):'').prop( "disabled", a.remove );
                                this.h.find('#b-s2').empty().append(new Option()).append(a?Sub.arr.options(a.subconto_type2):'').prop( "disabled", a.remove );
                            }else if(!await ui.confirm('Хотите изменить')){
                                let v=parseInt(this.h.find('#b-account').val()),a=Acc.get(v);
                                if(t==='b-s1'){
                                    $('#b-s1').val(a.subconto_type1);
                                }else if(t==='b-s2'){
                                    $('#b-s2').val(a.subconto_type2);
                                }
                            }
                        }).update(function (r){
                            if(r.accounts){
                                Acc.puts(r.accounts).then(()=>{
                                    this.reload();
                                });
                            }
                        }).newAcc(function (r){
                            if(r.accounts){
                                Acc.puts(r.accounts).then(()=>{
                                    this.reload();
                                });
                            }
                        }).open();
                    })
                    $('.a-open-reports').click(e=>{
                        new ReportsForm(DB).on((e,r)=>{
                            switch (e){
                                case 'report':
                                    let R = JSON.parse(r);
                                    R.dataSource.filename=url_csv;
                                    pivot.setReport(R);

                                    break;
                                case 'delete':
                                    server.post({command:"del-report",value:r},r=>{
                                        if (r.error){
                                            ui.alert(r.error);
                                        }else{
                                            DB.R.where({id:parseInt(r.value)}).delete().then(r=>{
                                                ui.alert("OK");
                                            })
                                        }
                                    },e=>{
                                        console.log(e);
                                    })
                                    break;
                            }
                        });
                    });
                    $('.a-report-server').click(e=>{
                        let html = "<p >Сохранить текущий отчет и отправит на сервер.</p>" +
                            "<hr><br>"+
                            "<form method='post'>" +
                            //"<fieldset>" +
                            "<label for='name'>Имя:</label>" +
                            "<input type='text' name='name' id='name' class='text ui-widget-content ui-corner-all'>" +
                            "<input type='hidden' name='command' value='save-report'>" +
                            //"</fieldset>" +
                            "</form>";
                        $('<div></div>').dialog({
                            height: 'auto',
                            resizable: false,
                            modal: true, title: "Сохранить отчет",
                            open: function (){
                                $(this).html(html);
                                $($(this).find("form")[0]).on('submit',function (e){
                                    let form = new FormData(e.currentTarget);
                                    const o = Object.fromEntries(form.entries());
                                    o.value = pivot.getReport();
                                    server.post(o,r=>{
                                        if (r.error){
                                            ui.alert(r.error);
                                        }else
                                            ui.alert("OK");
                                    },e=>{
                                        console.log(e);
                                    })
                                    $(this).dialog("close");
                                    return false;
                                }.bind(this))
                            },
                            buttons: {
                                Да: function (){
                                    $($(this).find("form")[0]).submit();
                                    //console.log(form);
                                },
                                Нет: function (){$(this).dialog("close");}
                            }
                        });
                        console.log("server");
                    });
                    $('.a-acc-table').click(e=>{
                        $('.acc-table-box').show();
                    });
                    $('.acc-table-box .e-close-button').click(e=>{
                        $('.acc-table-box').hide();
                    })
                    $('.a-sub-book').click(e=>{
                        new SubcontoForm().load(function (i){
                            let h=this.h;
                            h[0].reset();
                            h.find('#s-type,#s-sub').empty()
                            if(Sub.arr.length)
                                h.find('#s-type').append(Sub.arr.options(i?i:Sub.arr[0].id));
                        }).change(async function(e){
                            let t=e.target.id,v=parseInt($('#'+t).val()),st=Sub.getType(v);
                            if(t==='s-type'&&v&& st){
                                this.h.find('#s-sub').empty().append(st.options()).prop( "disabled", st.remove );
                            }
                        }).event(function (e){
                            if (e.types) {
                                Sub.putTypes(e.types).then(r => {
                                    this.reload(e.index);
                                    this.h.find(`#s-type option[value='${e.index}']`).prop('selected', true);
                                    //this.h.find('#s-type').val(e['st-id']).change();
                                    this.h.find('#s-type').change();
                                })
                            }else if(e.subcontos){
                                Sub.putSubcontos(e.subcontos).then(r=>{
                                    this.reload();
                                    this.h.find('#s-type').val(e['st-id']).change();
                                })
                            }
                        }).open();
                    })
                    $('.a-sync').click(e=>{
                        Sub.sync().catch(e=>{
                            ui.alert(e);
                        });
                    })
                    $('.d-update').click(e=>{
                        ui.confirm("Полное обновление данных. Может занять некоторое время").then(async a=>{
                            if(a){
                                block(1)
                                await DB.sync().finally(()=>{
                                    newUpdate(DB,()=>{
                                        Entries.U(DB);
                                        creatCSV();
                                    });
                                    block(0)
                                });
                            }
                        })
                    })
                })
            </script>
            <div id="loader" style="display: none" class="loader"></div>
            <nav>
                <?php
                    $user_type = isset($_SESSION['user_data'])?$_SESSION['user_data']->{'type'}:null;
                    if($user_type&&in_array(1,$user_type)){
                        echo "<div class='a-report-server' title='Сохранить отчет на сервере'><i class='fas fa-cloud-upload-alt'></i></div>";
                        echo "<div class='a-open-reports' title='Отчеты'><i class='fas fa-th-list'></i></div>";
                        echo "<div class='a-acc-table' title='Таблица проводок'><i class='fas fa-table'></i></div>";
                        echo "<div class='a-sub-book' title='Субконто'><i class='fas fa-list-alt'></i></div>";
                        echo "<div class='a-acc-book' title='Счета'><i class='fas fa-window-maximize'></i></div>";
                        echo "<div class='a-sync' title='Обновить справочники'><i class='fas fa-sync-alt'></i></div>";
                    }
                ?>
                <div class="d-update" title="Синхронизация с сервером"><i class="fas fa-database"></i></div>
                <div class="new_entry" title="Новая проводка"><i class="fas fa-plus-circle"></i></div>
                <form method="post" class="form-out">
                    <button type="submit" name="out" class="background-problem" style="width: 30px;"><i class='fas fa-sign-out-alt'></i></button>
                </form>
            </nav>
            <main>
                <div id="datalists"></div>
                <div class="acc-table-box" hidden="hidden">
                    <div>Таблица проводок <span class="new_entry" title="Новая проводка"><i class="fas fa-plus-circle"></i></span></div>
                    <button class='e-close-button'>×</button>
                    <!--<table id="entries_table" style="font-size: small;"></table>-->
                    <table id="entries_table" class="display" style="font-size: small;width:100%;user-select: none;white-space: nowrap;" ></table>
                </div>
                <div id="pivot_container" class="tabcontent"></div>
            </main>

        <?php }?>
</body>
</html>
