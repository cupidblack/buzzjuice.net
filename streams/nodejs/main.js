/**
 * main.js - WoWonder Node.js Real-Time Messaging Server (cPanel/Passenger compatible)
 * Uses Socket.IO with BPBM/BuddyBoss sync fallback, media-aware, and extensive logging.
 * Uses node-fetch@2 for HTTP requests to avoid undici/WebAssembly memory errors.
 */

// REQUIRED: run `npm install node-fetch@2` before using this file

const moment = require("moment");
const fs = require("fs");
const path = require("path");
const express = require("express");
const { Sequelize, DataTypes } = require("sequelize");
const fetch = require("node-fetch"); // node-fetch@2
const app = express();

let ctx = {};
let server, io;

const configFile = require("./config.json");
const listeners = require("./listeners/listeners");

function getConfig(key, fallback = undefined) {
  if (process.env[key]) return process.env[key];
  if (configFile[key.toLowerCase()]) return configFile[key.toLowerCase()];
  return fallback;
}

function fileExistsAndIsFile(filePath) {
  try {
    return fs.existsSync(filePath) && fs.lstatSync(filePath).isFile();
  } catch (e) {
    console.error(`[ERROR] Checking file existence failed for ${filePath}:`, e);
    return false;
  }
}

function logToFile(msg, error = false) {
  const logFile = path.join(__dirname, "logs", "server.log");
  const logDir = path.dirname(logFile);
  try {
    if (!fs.existsSync(logDir)) fs.mkdirSync(logDir, { recursive: true });
    const prefix = `[${moment().format("YYYY-MM-DD HH:mm:ss")}]${error ? " [ERROR]" : " [INFO]"}`;
    fs.appendFileSync(logFile, `${prefix} ${msg}\n`);
  } catch (e) {
    console.error("[FATAL] Unable to write to log file:", e);
  }
}

// ---- START: Fallback Message Sync Logic ----
async function syncMessageToWordPressFallback({ from_id, to_id, message, media_url }) {
  const fallbackLogFile = path.join(__dirname, "..", "data", "sync_messages", "logs", "fallback_usage.log");

  const logFallback = (msg, level = "info") => {
    try {
      if (!fs.existsSync(path.dirname(fallbackLogFile))) {
        fs.mkdirSync(path.dirname(fallbackLogFile), { recursive: true });
      }
      const prefix = `[${moment().format("YYYY-MM-DD HH:mm:ss")}] [${level.toUpperCase()}]`;
      fs.appendFileSync(fallbackLogFile, `${prefix} ${msg}\n`);
    } catch (err) {
      console.error("[FATAL] Logging fallback message failed:", err.message);
    }
  };

  const messageText = (message || "").trim();
  const cleanMediaUrl = (media_url || "").trim();

  if (!from_id || !to_id || !messageText) {
    logFallback(`Invalid message for fallback. from_id=${from_id}, to_id=${to_id}, message="${messageText}"`, "error");
    return false;
  }

  const timeoutMs = 5000;

  // 1. Try BPBM REST API
  try {
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), timeoutMs);

    const bpbmPayload = new URLSearchParams({
      sender_id: from_id,
      recipients: JSON.stringify([to_id]),
      message: messageText,
    });
    if (cleanMediaUrl) bpbmPayload.append("attachment", cleanMediaUrl);

    const res = await fetch("https://buzzjuice.net/wp-json/bp-better-messages/v1/send-message", {
      method: "POST",
      body: bpbmPayload,
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      signal: controller.signal,
    });
    clearTimeout(timeout);

    if (res.ok) {
      logFallback(`✅ BPBM Sync Success: ${from_id} → ${to_id}. Media: ${cleanMediaUrl || "none"}`);
      return true;
    }
  } catch (err) {
    logFallback(`⚠️ BPBM Sync Failed: ${err.message}`, "warn");
  }

  // 2. Try Custom REST API
  try {
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), timeoutMs);

    const res = await fetch("https://buzzjuice.net/wp-json/buddyboss-sync/v1/new-message", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ from_id, to_id, message: messageText, media_url: cleanMediaUrl }),
      signal: controller.signal,
    });
    clearTimeout(timeout);

    const json = await res.json();
    if (json?.status === "ok") {
      logFallback(`✅ Custom REST Sync Success: ${from_id} → ${to_id}. Media: ${cleanMediaUrl || "none"}`);
      return true;
    }
  } catch (err) {
    logFallback(`❌ Custom REST Sync Failed: ${err.message}`, "error");
  }

  logFallback(`❌ All Sync Fallbacks Failed. from_id=${from_id}, to_id=${to_id}, message="${messageText}"`, "error");
  return false;
}
// ---- END Fallback Logic ----

async function loadConfig(ctx) {
  try {
    const configRows = await ctx.wo_config.findAll({ raw: true });
    ctx.globalconfig = {};
    for (const c of configRows) {
      ctx.globalconfig[c.name] = c.value;
    }

    ctx.globalconfig.site_url = getConfig("SITE_URL", configFile.site_url);
    ctx.globalconfig.theme_url = `${ctx.globalconfig.site_url}/themes/${ctx.globalconfig.theme}`;
    ctx.globalconfig.s3_site_url = ctx.globalconfig.bucket_name
      ? `https://${ctx.globalconfig.bucket_name}.s3.amazonaws.com`
      : "https://test.s3.amazonaws.com";
    ctx.globalconfig.s3_site_url_2 = ctx.globalconfig.bucket_name_2
      ? `https://${ctx.globalconfig.bucket_name_2}.s3.amazonaws.com`
      : "https://test.s3.amazonaws.com";

    if (ctx.globalconfig.ftp_endpoint?.startsWith("https://")) {
      ctx.globalconfig.ftp_endpoint = ctx.globalconfig.ftp_endpoint.replace("https://", "");
    }

    const port = process.env.PORT
      ? parseInt(process.env.PORT, 10)
      : (ctx.globalconfig.nodejs_ssl === "1" || getConfig("NODEJS_SSL") === "1")
        ? parseInt(getConfig("NODEJS_SSL_PORT", ctx.globalconfig.nodejs_ssl_port || 3002), 10)
        : parseInt(getConfig("NODEJS_PORT", ctx.globalconfig.nodejs_port || 3001), 10);
    ctx.serverPort = port;

    if (ctx.globalconfig.nodejs_ssl === "1" || getConfig("NODEJS_SSL") === "1") {
      const keyPath = getConfig("NODEJS_KEY_PATH", ctx.globalconfig.nodejs_key_path);
      const certPath = getConfig("NODEJS_CERT_PATH", ctx.globalconfig.nodejs_cert_path);
      if (!fileExistsAndIsFile(keyPath) || !fileExistsAndIsFile(certPath)) {
        logToFile(`[FATAL] SSL enabled but missing cert or key: ${keyPath}, ${certPath}`, true);
        process.exit(1);
      }
      const https = require("https");
      const options = {
        key: fs.readFileSync(path.resolve(keyPath)),
        cert: fs.readFileSync(path.resolve(certPath)),
      };
      server = https.createServer(options, app);
    } else {
      server = require("http").createServer(app);
    }
    logToFile(`[INFO] Server prepared on port ${ctx.serverPort} (SSL: ${ctx.globalconfig.nodejs_ssl === "1"})`);
  } catch (error) {
    logToFile(`[ERROR] loadConfig failed: ${error.stack || error}`, true);
    throw error;
  }
}

async function loadLangs(ctx) {
  const langs = await ctx.wo_langs.findAll({ raw: true });
  ctx.globallangs = {};
  langs.forEach(l => {
    ctx.globallangs[l.lang_key] = l.english;
  });
}

async function init() {
  const sqlHost = getConfig("SQL_DB_HOST", configFile.sql_db_host || "127.0.0.1");
  const sqlUser = getConfig("SQL_DB_USER", configFile.sql_db_user);
  const sqlPass = getConfig("SQL_DB_PASS", configFile.sql_db_pass);
  const sqlName = getConfig("SQL_DB_NAME", configFile.sql_db_name);

  if (!sqlHost || !sqlUser || !sqlPass || !sqlName) {
    logToFile("[FATAL] SQL config is incomplete", true);
    process.exit(1);
  }

  const sequelize = new Sequelize(sqlName, sqlUser, sqlPass, {
    host: sqlHost,
    dialect: "mysql",
    logging: msg => logToFile(`[Sequelize] ${msg}`),
    pool: { max: 20, min: 0, idle: 10000 }
  });

  const models = [
    "wo_messages", "wo_userschat", "wo_users", "wo_notifications",
    "wo_groupchat", "wo_groupchatusers", "wo_videocalles", "wo_audiocalls",
    "wo_appssessions", "wo_langs", "wo_config", "wo_blocks",
    "wo_followers", "wo_hashtags", "wo_posts", "wo_comments",
    "wo_comment_replies", "wo_pages", "wo_groups", "wo_events",
    "wo_userstory", "wo_reactions_types", "wo_reactions", "wo_blog_reaction",
    "wo_mute"
  ];

  for (const model of models) {
    ctx[model] = require(`./models/${model}`)(sequelize, DataTypes);
  }

  ctx.socketIdUserHash = {};
  ctx.userHashUserId = {};
  ctx.userIdCount = {};
  ctx.userIdChatOpen = {};
  ctx.userIdSocket = [];
  ctx.userIdExtra = {};
  ctx.userIdGroupChatOpen = {};

  await loadConfig(ctx);
  await loadLangs(ctx);
  await sequelize.authenticate();
  logToFile("[INFO] DB connected successfully.");
}

async function main() {
  try {
    await init();

    app.get("/", (req, res) => res.send("Node.js backend is running."));

    io = require("socket.io")(server, {
      allowEIO3: true,
      cors: { origin: true, credentials: true },
    });

    if (ctx.globalconfig.redis === "Y") {
      try {
        const redisAdapter = require("socket.io-redis");
        io.adapter(redisAdapter({ host: "localhost", port: ctx.globalconfig.redis_port }));
        logToFile(`[INFO] Redis adapter enabled on port ${ctx.globalconfig.redis_port}`);
      } catch (e) {
        logToFile(`[ERROR] Redis adapter failed: ${e.stack || e}`, true);
      }
    }

    io.on("connection", async socket => {
      logToFile(`[SOCKET] Connected: ${socket.id}`);
      await listeners.registerListeners(socket, io, ctx, syncMessageToWordPressFallback);
    });

    server.listen(ctx.serverPort, () => {
      const msg = `Server running on port ${ctx.serverPort}`;
      logToFile(msg);
      console.log(msg);
    });
  } catch (err) {
    logToFile(`[FATAL] main() failed: ${err.stack || err}`, true);
    console.error("[FATAL]", err);
    process.exit(1);
  }
}

main();