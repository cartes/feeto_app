import axios from "axios";
window.axios = axios;

window.axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";

import Echo from "laravel-echo";
import Pusher from "pusher-js";

window.Pusher = Pusher;

const reverbKey = import.meta.env.VITE_REVERB_APP_KEY || (window.laravelReverbConfig && window.laravelReverbConfig.key);
const reverbHost = import.meta.env.VITE_REVERB_HOST || (window.laravelReverbConfig && window.laravelReverbConfig.host);
const reverbPort = import.meta.env.VITE_REVERB_PORT || (window.laravelReverbConfig && window.laravelReverbConfig.port);
const reverbScheme = import.meta.env.VITE_REVERB_SCHEME || (window.laravelReverbConfig && window.laravelReverbConfig.scheme);

if (reverbKey) {
    window.Echo = new Echo({
        broadcaster: "reverb",
        key: reverbKey,
        wsHost: reverbHost,
        wsPort: reverbPort ?? 80,
        wssPort: reverbPort ?? 443,
        forceTLS: (reverbScheme ?? "https") === "https",
        enabledTransports: ["ws", "wss"],
    });
} else {
    console.warn("Reverb key not found. Real-time features will be disabled.");
}

