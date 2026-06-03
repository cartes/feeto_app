import axios from "axios";
window.axios = axios;

window.axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";

import Echo from "laravel-echo";
import Pusher from "pusher-js";

window.Pusher = Pusher;

const runtimeReverbConfig = window.laravelReverbConfig || {};
const reverbEnabled = runtimeReverbConfig.enabled ?? Boolean(import.meta.env.VITE_REVERB_APP_KEY && import.meta.env.VITE_REVERB_HOST);
const reverbKey = runtimeReverbConfig.key || import.meta.env.VITE_REVERB_APP_KEY;
const reverbHost = runtimeReverbConfig.host || import.meta.env.VITE_REVERB_HOST;
const reverbPort = runtimeReverbConfig.port || import.meta.env.VITE_REVERB_PORT;
const reverbScheme = runtimeReverbConfig.scheme || import.meta.env.VITE_REVERB_SCHEME;

if (reverbEnabled && reverbKey && reverbHost) {
    window.Echo = new Echo({
        broadcaster: "reverb",
        key: reverbKey,
        wsHost: reverbHost,
        wsPort: reverbPort ?? 80,
        wssPort: reverbPort ?? 443,
        forceTLS: (reverbScheme ?? "https") === "https",
        enabledTransports: ["ws", "wss"],
    });
} else if (reverbEnabled) {
    console.warn("Reverb key not found. Real-time features will be disabled.");
}
