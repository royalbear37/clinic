<style>
    :root {
        --primary: #2d323a;
        --accent: #d1b656;
        --bg: #f9f7f4;
        --card-bg: #fffefc;
        --text: #1f252d;
        --subtext: #6b6b76;
        --border: #e2ded6;
    }

    /* 聊天浮動按鈕 */
    #chat-btn {
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: var(--accent);
        color: white;
        padding: 10px 16px;
        font-size: 15px;
        border-radius: 999px;
        cursor: pointer;
        z-index: 9999;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    }

    /* 主聊天視窗 */
    #chat-box {
        display: none;
        position: fixed;
        bottom: 80px;
        right: 20px;
        width: 340px;
        height: 480px;
        background: var(--card-bg);
        border: 1px solid var(--border);
        border-radius: 12px;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        z-index: 9998;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    #chat-box .header {
        background: var(--primary);
        color: var(--accent);
        padding: 10px;
        text-align: center;
        font-weight: bold;
        font-size: 1.05em;
    }

    #chat-log {
        flex: 1;
        overflow-y: auto;
        padding: 12px;
        background-color: var(--bg);
    }

    .input-row {
        display: flex;
        padding: 10px;
        border-top: 1px solid var(--border);
        gap: 6px;
        background: var(--card-bg);
    }

    #chat-box input[type="text"] {
        flex: 1;
        border: 1px solid var(--border);
        padding: 6px;
        border-radius: 6px;
        font-size: 14px;
        background: white;
        color: var(--text);
    }

    #chat-box button {
        background: var(--accent);
        color: white;
        border: none;
        padding: 6px 12px;
        border-radius: 6px;
        cursor: pointer;
    }

    #chat-box button:hover {
        background: #bca33f;
    }

    /* 對話訊息區塊 */
    .chat-entry {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        margin-bottom: 12px;
    }

    .chat-entry img {
        width: 32px;
        height: 32px;
        border-radius: 9999px;
    }

    .chat-content {
        display: flex;
        flex-direction: column;
        gap: 4px;
        max-width: 260px;
    }

    .chat-header {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 11px;
        color: var(--subtext);
    }

    .chat-bubble {
        padding: 8px 12px;
        border-radius: 16px;
        font-size: 14px;
        line-height: 1.6;
    }

    /* 使用者對話樣式（深灰藍） */
    .chat-user {
        align-self: flex-end;
        flex-direction: row-reverse;
    }

    .chat-user .chat-bubble {
        background-color: var(--primary);
        color: white;
        border-top-right-radius: 0;
    }

    /* AI 回應樣式（偏亮金） */
    .chat-entry:not(.chat-user) .chat-bubble {
        background-color: #f5e5a2;
        color: var(--text);
        border-top-left-radius: 0;
    }
</style>


<div id="chat-btn">💬 聊天</div>
<div id="chat-box">
    <div class="header">AI 小幫手</div>
    <div id="chat-log"></div>
    <div class="input-row">
        <input type="text" id="user-input" placeholder="輸入您的問題">


        <button onclick="sendMessage()">送出</button>
    </div>
</div>

<script>
    document.getElementById("chat-btn").onclick = function() {
        const box = document.getElementById("chat-box");
        box.style.display = box.style.display === "none" ? "flex" : "none";
    };

    function appendMessage(content, name, avatar, time, isUser = false) {
        const container = document.createElement("div");
        container.className = "chat-entry" + (isUser ? " chat-user" : "");

        const img = document.createElement("img");
        img.src = avatar;
        img.alt = name;

        const contentBox = document.createElement("div");
        contentBox.className = "chat-content";

        const header = document.createElement("div");
        header.className = "chat-header";
        header.innerHTML = `<strong>${name}</strong><span>${time}</span>`;

        const bubble = document.createElement("div");
        bubble.className = "chat-bubble";
        bubble.innerHTML = content;

        contentBox.appendChild(header);
        contentBox.appendChild(bubble);

        container.appendChild(img);
        container.appendChild(contentBox);

        document.getElementById("chat-log").appendChild(container);
        document.getElementById("chat-log").scrollTop = document.getElementById("chat-log").scrollHeight;
    }

    function sendMessage() {
        const input = document.getElementById("user-input");
        const msg = input.value.trim();
        if (!msg) return;

        const now = new Date().toLocaleTimeString([], {
            hour: '2-digit',
            minute: '2-digit'
        });
        appendMessage(msg, "你", "/clinic/ai_assistant/assets/user.png", now, true);

        fetch("/clinic/ai_assistant/chat_api.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    message: msg
                })
            })
            .then(res => res.json())
            .then(data => {
                const replyText = data.reply || "(⚠️ 無回應)";
                appendMessage(replyText.replace(/\n/g, '<br>'), "AI 小幫手", "/clinic/ai_assistant/assets/ai.png", now, false);
            })
            .catch(err => {
                console.error("❌ 錯誤：", err);
                appendMessage("無法取得回應，請稍後再試。", "AI 小幫手", "/clinic/ai_assistant/assets/ai.png", now, false);
            });

        input.value = "";
    }
</script>