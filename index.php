<?php
    require_once('const.php');
    $lang = Lang::JAPANESE;
    $langcode_list = Lang::getConstants();
    $trans_langcode_list = Lang::getMainLangList();
    $param_lang = $_GET['lang'];
    // サイトのロケール設定
    if (isset($param_lang) && in_array($param_lang, $trans_langcode_list)) {
        $lang = $param_lang;
    }

    // 言語セット取得
    $messages = parse_ini_file('messages/' . $lang . '.properties', true);
    // BCP 47言語タグへの変換セット取得(js用)
    $bcp_47_list_json = json_encode(LangCodeSet::BCP_47_LIST);
    // システムプロパティ取得
    $system_properties = parse_ini_file('messages/system.properties');
?>
<!DOCTYPE html>
<html lang='<?=$lang?>'>
<head>
    <meta charset='UTF-8'>
    <meta http-equiv='X-UA-Compatible' content='IE=edge'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title><?=$messages['settings']['title']?></title>
    <link rel='apple-touch-icon' type='image/png' href='/icon/apple-touch-icon-180x180.png'>
    <link rel='icon' type='image/png' href='/icon/icon-192x192.png'>
    <link href='/css/bootstrap.min.css' rel='stylesheet'>
    <link href='/css/style.min.css' rel='stylesheet'>
    <script src='/js/bootstrap.bundle.min.js'></script>
</head>
<body>
    <div class='container p-2 vh-100 fix-height'>
        <div class='row div-scroll-md'>
            <div class='col-md border' translate='no'>
                <h4><?=$messages['text']['tras_language']?></h4>
                <div id='source_area'></div>
                <div id='source_area_old'></div>
            </div>
            <div class='col-md border'>
                <h4 translate='no'><?=$messages['text']['browser_translation']?></h4>
                <div id='browser_trans_area'></div>
                <div id='browser_trans_area_old'></div>
            </div>
            <div class='col-md border' id="papago_area" translate='no'>
                <h4><?=$messages['text']['papago_translation']?></h4>
                <div id='papago_trans_area'></div>
            </div>
        </div>
        <div class='row mt-2'>
            <div class='col-auto'>
                <!-- 音声認識する言語 -->
                <select id='source_lang' class='form-select'>
                <?php
                    foreach ($langcode_list as $langcode) {
                ?>
                    <!-- 日本版の場合は韓国語をデフォルト値にする。その他は日本語をデフォルト値 -->
                    <option value='<?=$langcode?>' <?=$lang === Lang::JAPANESE && $langcode === Lang::KOREAN ? 'selected' : ''?>><?=$messages['languages'][$langcode]?></option>
                <?php
                    }
                ?>
                </select>
            </div>
            <div class='col-auto d-flex align-items-center'>
                <label>→</label>
            </div>
            <div class='col-auto'>
                <!-- 翻訳言語 -->
                <select id='target_lang' class='form-select'>
                    <option value=''></option>
                <?php
                    foreach ($trans_langcode_list as $langcode) {
                ?>
                    <option value='<?=$langcode?>' <?=$langcode === $lang ? 'selected': '' ?>><?=$messages['languages'][$langcode]?></option>
                <?php
                    }
                ?>
                </select>
            </div>
        </div>
        <div class='row mt-2' translate='no'>
            <div class="col-auto">
                <input type="text" class="form-control" id="papagoAPI_client_id" placeholder="Naver Client Id">
            </div>
            <div class="col-auto">
                <input type="text" class="form-control" id="papagoAPI_client_secret" placeholder="Naver Client Secret">
            </div>
            <div class="col-auto d-flex align-items-center">
                <input type="radio" class="form-check-input" name="papago_check" id="papago_check_on" value="0" disabled>
                <label class="form-check-label mx-1" for="papago_check_on">P-ON</label>
                <input type="radio" class="form-check-input" name="papago_check" id="papago_check_off" value="1" checked disabled>
                <label class="form-check-label mx-1" for="papago_check_off">P-OFF</label>
            </div>
        </div>
        <footer class="footer">
            <div class="text-center">
                <span class="text-muted"><?=$system_properties['copyright']?></span>
            </div>
        </footer>
    </div>
    <script src='/jquery/jquery-3.6.4.min.js'></script>
    <script>
        const bcp_47_list =JSON.parse('<?=$bcp_47_list_json?>');

        const source_lang = $('#source_lang');
        const target_lang = $('#target_lang');
        const papago_area = $('#papago_area');
        const papagoAPI_client_id = $('#papagoAPI_client_id');
        const papagoAPI_client_secret = $('#papagoAPI_client_secret');
        const papago_check_radio = $('input:radio[name="papago_check"]');

        let flag_speech = 0;
        let flag_papago = 0;
        let papagoAPI_client_id_key = localStorage.getItem('papagoAPI_client_id_key');
        let papagoAPI_client_secret_key = localStorage.getItem('papagoAPI_client_secret_key');
        let source_lang_val = source_lang.val();
        let target_lang_val = target_lang.val();

        // 初期表示処理
        $(function() {
            // papago_areaを非表示
            papago_area.hide();
            // ローカルストレージからpapagoAPIを取得しテキストボックスにセット
            if(papagoAPI_client_id_key && papagoAPI_client_secret_key) {
                papagoAPI_client_id.val(papagoAPI_client_id_key);
                papagoAPI_client_secret.val(papagoAPI_client_secret_key);
                papago_check_radio.removeAttr('disabled');
            }
            // 音声認識開始
            speechToText(bcp_47_list);
        });

        // 音声認識して転記するメソッド
        function speechToText() {
            let final_flg = 0;
            const source_area = $('#source_area')[0];
            const browser_trans_area = $('#browser_trans_area')[0];

            // Web Speech APIの設定
            SpeechRecognition = webkitSpeechRecognition || SpeechRecognition;
            const recognition = new SpeechRecognition();
            recognition.lang = bcp_47_list[source_lang_val];
            recognition.interimResults = true;

            let final_transcript = ''; // 確定した(黒の)認識結果
            recognition.onresult =  function(event) {
                let interim_transcript = ''; // 暫定(灰色)の認識結果
                for (let i = event.resultIndex; i < event.results.length; i++) {
                    let transcript = event.results[i][0].transcript;
                    if (event.results[i].isFinal) {
                        final_transcript += transcript;
                        final_flg = 1;
                    } else {
                        interim_transcript += transcript;
                        flag_speech = 1;
                    }
                }
                if(final_flg === 1) {
                    source_area.innerHTML = '';
                    browser_trans_area.innerHTML = '';
                    const source_area_old = $('#source_area_old')[0];
                    const browser_trans_area_old = $('#browser_trans_area_old')[0];
                    let source_element = document.createElement('p');
                    let browser_trans_element = document.createElement('p');
                    source_element.style.cssText = "margin-bottom: 3px !important;";
                    browser_trans_element.style.cssText = "margin-bottom: 3px !important;";
                    source_element.textContent = final_transcript;
                    source_area_old.prepend(source_element);
                    browser_trans_element.textContent = final_transcript;
                    browser_trans_area_old.prepend(browser_trans_element);

                    // papago
                    requestPapago(final_transcript);
                    speechToText();
                }else{
                    source_area.innerHTML = final_transcript + '<i style="color: #ff333f;">' + interim_transcript + '</i>';
                    browser_trans_area.innerHTML = final_transcript + '<i style="color: #ff333f;">' + interim_transcript + '</i>';
                }
            };

            recognition.onsoundend = function() {
                speechToText();
            };

            recognition.onerror = function() {
                if(flag_speech === 0){
                    speechToText();
                }
            };
            
            flag_speech = 0;
            recognition.start();
        }

        function requestPapago(final_transcript) {
            // papagoチェックがONじゃないと処理しない:(
            if (flag_papago !== 1){
                return;
            }
            // 必要なパラメータがない場合は処理を終了
            if (!(papagoAPI_client_id_key && papagoAPI_client_secret_key && source_lang_val && target_lang_val && final_transcript)) {
                return;
            }
            const req = new XMLHttpRequest();
            const papago_trans_area = document.querySelector('#papago_trans_area');
            const papago_url = `<?=$system_properties['papago_ajax_base_url']?>?client_id=${papagoAPI_client_id_key}&client_secret=${papagoAPI_client_secret_key}&source=${source_lang_val}&target=${target_lang_val}&text=${final_transcript}`;
            req.open('GET', papago_url, true);
            req.responseType = "text";
            req.addEventListener('load', function(event){
                const jsonObj = JSON.parse(req.responseText);
                let new_element = document.createElement('p');
                new_element.style.cssText = "margin-bottom: 3px !important;";
                new_element.textContent = jsonObj.message.result.translatedText + '\n';
                papago_trans_area.prepend(new_element);
                console.log('seikou');
                console.log(req);
            });
            req.send(null);
        }

        papagoAPI_client_id.change(function(){
            let papagoAPI_client_id_val = $.trim($(this).val());
            if (papagoAPI_client_id_val) {
                papagoAPI_client_id_key = papagoAPI_client_id_val;
                localStorage.setItem('papagoAPI_client_id_key', papagoAPI_client_id_val);
                if (papagoAPI_client_secret_key && target_lang_val) {
                    papago_check_radio.removeAttr('disabled');
                }
            } else {
                papagoAPI_client_id_key = "";
                localStorage.setItem('papagoAPI_client_id_key', "");
                papago_check_radio.val(["1"]);
                papago_check_radio.prop('disabled', true);
                changeRadio();
            }
        });

        papagoAPI_client_secret.change(function(){
            let papagoAPI_client_secret_val = $.trim($(this).val());
            if (papagoAPI_client_secret_val) {
                papagoAPI_client_secret_key = papagoAPI_client_secret_val;
                localStorage.setItem('papagoAPI_client_secret_key', papagoAPI_client_secret_val);
                if (papagoAPI_client_id_key && target_lang_val) {
                    papago_check_radio.removeAttr('disabled');
                }
            } else {
                papagoAPI_client_secret_key = "";
                localStorage.setItem('papagoAPI_client_secret_key', "");
                papago_check_radio.val(["1"]);
                papago_check_radio.prop('disabled', true);
                changeRadio();
            }
        });

        // papagoチェック変更時のイベント
        papago_check_radio.change(changeRadio);

        function changeRadio() {
            if ($('input:radio[name="papago_check"]:checked').val() === "0") {
                papago_area.show();
                flag_papago = 1;
            } else {
                papago_area.hide();
                flag_papago = 0;
            }
        }

        // 翻訳言語が変更したら
        target_lang.change(function(){
            target_lang_val = $(this).val();
            if (target_lang_val && papagoAPI_client_id_key && papagoAPI_client_secret_key) {
                papago_check_radio.removeAttr('disabled');
            } else {
                papago_check_radio.val(["1"]);
                papago_check_radio.prop('disabled', true);
                changeRadio();
            }
        });

        // 音声の言語が変更したら
        source_lang.change(function(){
            source_lang_val = $(this).val();
        });
    </script>
</body>
</html>