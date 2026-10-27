import { a as useForm, d as vue_exports, l as server_renderer_exports } from "./vendor-core-CciLBNgM.js";
import { a as _sfc_main$5, i as PrimaryButton_default, n as _sfc_main$7, o as _sfc_main$3, r as _sfc_main$4, s as _sfc_main$6, t as _sfc_main$2 } from "../ssr.js";
//#region resources/js/Components/ForgotPasswordModal.vue
var _sfc_main$1 = {
	__name: "ForgotPasswordModal",
	__ssrInlineRender: true,
	props: {
		show: {
			type: Boolean,
			default: false
		},
		maxWidth: {
			type: String,
			default: "md"
		},
		closeable: {
			type: Boolean,
			default: true
		},
		initialEmail: {
			type: String,
			default: ""
		}
	},
	emits: ["close", "back-to-login"],
	setup(__props, { emit: __emit }) {
		const props = __props;
		const emit = __emit;
		const statusMessage = (0, vue_exports.ref)("");
		const form = useForm({ email: props.initialEmail });
		(0, vue_exports.watch)(() => props.show, (newVal) => {
			if (newVal) {
				statusMessage.value = "";
				if (props.initialEmail) form.email = props.initialEmail;
			}
		});
		const submit = () => {
			statusMessage.value = "";
			form.post(route("password.email"), { onSuccess: (page) => {
				statusMessage.value = page.props.flash?.status || "Hemos enviado el código y enlace de recuperación a tu correo electrónico.";
			} });
		};
		const close = () => {
			emit("close");
			form.reset();
			form.clearErrors();
			statusMessage.value = "";
		};
		const handleBackToLogin = () => {
			close();
			emit("back-to-login");
		};
		return (_ctx, _push, _parent, _attrs) => {
			_push((0, server_renderer_exports.ssrRenderComponent)(_sfc_main$2, (0, vue_exports.mergeProps)({
				show: __props.show,
				"max-width": __props.maxWidth,
				closeable: __props.closeable,
				onClose: close
			}, _attrs), {
				default: (0, vue_exports.withCtx)((_, _push, _parent, _scopeId) => {
					if (_push) {
						_push(`<div class="p-8"${_scopeId}><div class="flex items-center justify-between mb-6"${_scopeId}><div${_scopeId}><h2 class="text-2xl font-black text-gray-900 tracking-tight"${_scopeId}>Recuperar Contraseña</h2><p class="text-sm text-gray-500 mt-1"${_scopeId}>Ingresa tu correo para enviarte un código y enlace de recuperación</p></div><button class="text-gray-400 hover:text-gray-600 transition-colors"${_scopeId}><svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"${_scopeId}><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"${_scopeId}></path></svg></button></div>`);
						if (statusMessage.value) _push(`<div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-start gap-3"${_scopeId}><svg class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"${_scopeId}><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"${_scopeId}></path></svg><div${_scopeId}><p class="font-semibold"${_scopeId}>¡Correo enviado con éxito!</p><p class="mt-0.5 text-emerald-700"${_scopeId}>${(0, server_renderer_exports.ssrInterpolate)(statusMessage.value)}</p></div></div>`);
						else _push(`<!---->`);
						_push(`<form class="space-y-6"${_scopeId}><div${_scopeId}>`);
						_push((0, server_renderer_exports.ssrRenderComponent)(_sfc_main$3, {
							for: "recovery_email",
							value: "Correo electrónico",
							class: "text-gray-700 font-semibold mb-1.5"
						}, null, _parent, _scopeId));
						_push((0, server_renderer_exports.ssrRenderComponent)(_sfc_main$4, {
							id: "recovery_email",
							type: "email",
							class: "block w-full border-gray-200 focus:border-tech-orange focus:ring-tech-orange rounded-xl shadow-sm h-12",
							modelValue: (0, vue_exports.unref)(form).email,
							"onUpdate:modelValue": ($event) => (0, vue_exports.unref)(form).email = $event,
							required: "",
							autofocus: "",
							autocomplete: "username",
							placeholder: "ej: usuario@empresa.com"
						}, null, _parent, _scopeId));
						_push((0, server_renderer_exports.ssrRenderComponent)(_sfc_main$5, {
							class: "mt-2 text-sm text-red-600 font-medium",
							message: (0, vue_exports.unref)(form).errors.email
						}, null, _parent, _scopeId));
						_push(`</div><div class="space-y-3 pt-2"${_scopeId}>`);
						_push((0, server_renderer_exports.ssrRenderComponent)(PrimaryButton_default, {
							class: ["w-full h-12 justify-center bg-tech-orange hover:bg-[#CC6200] text-white font-bold rounded-xl shadow-lg shadow-tech-orange/20 transition-all active:scale-[0.98] text-base", { "opacity-25 pointer-events-none": (0, vue_exports.unref)(form).processing }],
							disabled: (0, vue_exports.unref)(form).processing
						}, {
							default: (0, vue_exports.withCtx)((_, _push, _parent, _scopeId) => {
								if (_push) if ((0, vue_exports.unref)(form).processing) _push(`<span class="flex items-center gap-2"${_scopeId}><svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"${_scopeId}><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"${_scopeId}></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"${_scopeId}></path></svg> Verificando y enviando... </span>`);
								else _push(`<span${_scopeId}>Enviar código y enlace</span>`);
								else return [(0, vue_exports.unref)(form).processing ? ((0, vue_exports.openBlock)(), (0, vue_exports.createBlock)("span", {
									key: 0,
									class: "flex items-center gap-2"
								}, [((0, vue_exports.openBlock)(), (0, vue_exports.createBlock)("svg", {
									class: "animate-spin h-5 w-5 text-white",
									xmlns: "http://www.w3.org/2000/svg",
									fill: "none",
									viewBox: "0 0 24 24"
								}, [(0, vue_exports.createVNode)("circle", {
									class: "opacity-25",
									cx: "12",
									cy: "12",
									r: "10",
									stroke: "currentColor",
									"stroke-width": "4"
								}), (0, vue_exports.createVNode)("path", {
									class: "opacity-75",
									fill: "currentColor",
									d: "M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
								})])), (0, vue_exports.createTextVNode)(" Verificando y enviando... ")])) : ((0, vue_exports.openBlock)(), (0, vue_exports.createBlock)("span", { key: 1 }, "Enviar código y enlace"))];
							}),
							_: 1
						}, _parent, _scopeId));
						_push(`<button type="button" class="w-full py-2 text-sm font-semibold text-gray-600 hover:text-gray-900 transition-colors text-center block"${_scopeId}> ← Volver a iniciar sesión </button></div></form></div>`);
					} else return [(0, vue_exports.createVNode)("div", { class: "p-8" }, [
						(0, vue_exports.createVNode)("div", { class: "flex items-center justify-between mb-6" }, [(0, vue_exports.createVNode)("div", null, [(0, vue_exports.createVNode)("h2", { class: "text-2xl font-black text-gray-900 tracking-tight" }, "Recuperar Contraseña"), (0, vue_exports.createVNode)("p", { class: "text-sm text-gray-500 mt-1" }, "Ingresa tu correo para enviarte un código y enlace de recuperación")]), (0, vue_exports.createVNode)("button", {
							onClick: close,
							class: "text-gray-400 hover:text-gray-600 transition-colors"
						}, [((0, vue_exports.openBlock)(), (0, vue_exports.createBlock)("svg", {
							class: "w-6 h-6",
							fill: "none",
							viewBox: "0 0 24 24",
							stroke: "currentColor"
						}, [(0, vue_exports.createVNode)("path", {
							"stroke-linecap": "round",
							"stroke-linejoin": "round",
							"stroke-width": "2",
							d: "M6 18L18 6M6 6l12 12"
						})]))])]),
						statusMessage.value ? ((0, vue_exports.openBlock)(), (0, vue_exports.createBlock)("div", {
							key: 0,
							class: "mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-start gap-3"
						}, [((0, vue_exports.openBlock)(), (0, vue_exports.createBlock)("svg", {
							class: "w-5 h-5 text-emerald-600 shrink-0 mt-0.5",
							fill: "none",
							viewBox: "0 0 24 24",
							stroke: "currentColor"
						}, [(0, vue_exports.createVNode)("path", {
							"stroke-linecap": "round",
							"stroke-linejoin": "round",
							"stroke-width": "2",
							d: "M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
						})])), (0, vue_exports.createVNode)("div", null, [(0, vue_exports.createVNode)("p", { class: "font-semibold" }, "¡Correo enviado con éxito!"), (0, vue_exports.createVNode)("p", { class: "mt-0.5 text-emerald-700" }, (0, vue_exports.toDisplayString)(statusMessage.value), 1)])])) : (0, vue_exports.createCommentVNode)("", true),
						(0, vue_exports.createVNode)("form", {
							onSubmit: (0, vue_exports.withModifiers)(submit, ["prevent"]),
							class: "space-y-6"
						}, [(0, vue_exports.createVNode)("div", null, [
							(0, vue_exports.createVNode)(_sfc_main$3, {
								for: "recovery_email",
								value: "Correo electrónico",
								class: "text-gray-700 font-semibold mb-1.5"
							}),
							(0, vue_exports.createVNode)(_sfc_main$4, {
								id: "recovery_email",
								type: "email",
								class: "block w-full border-gray-200 focus:border-tech-orange focus:ring-tech-orange rounded-xl shadow-sm h-12",
								modelValue: (0, vue_exports.unref)(form).email,
								"onUpdate:modelValue": ($event) => (0, vue_exports.unref)(form).email = $event,
								required: "",
								autofocus: "",
								autocomplete: "username",
								placeholder: "ej: usuario@empresa.com"
							}, null, 8, ["modelValue", "onUpdate:modelValue"]),
							(0, vue_exports.createVNode)(_sfc_main$5, {
								class: "mt-2 text-sm text-red-600 font-medium",
								message: (0, vue_exports.unref)(form).errors.email
							}, null, 8, ["message"])
						]), (0, vue_exports.createVNode)("div", { class: "space-y-3 pt-2" }, [(0, vue_exports.createVNode)(PrimaryButton_default, {
							class: ["w-full h-12 justify-center bg-tech-orange hover:bg-[#CC6200] text-white font-bold rounded-xl shadow-lg shadow-tech-orange/20 transition-all active:scale-[0.98] text-base", { "opacity-25 pointer-events-none": (0, vue_exports.unref)(form).processing }],
							disabled: (0, vue_exports.unref)(form).processing
						}, {
							default: (0, vue_exports.withCtx)(() => [(0, vue_exports.unref)(form).processing ? ((0, vue_exports.openBlock)(), (0, vue_exports.createBlock)("span", {
								key: 0,
								class: "flex items-center gap-2"
							}, [((0, vue_exports.openBlock)(), (0, vue_exports.createBlock)("svg", {
								class: "animate-spin h-5 w-5 text-white",
								xmlns: "http://www.w3.org/2000/svg",
								fill: "none",
								viewBox: "0 0 24 24"
							}, [(0, vue_exports.createVNode)("circle", {
								class: "opacity-25",
								cx: "12",
								cy: "12",
								r: "10",
								stroke: "currentColor",
								"stroke-width": "4"
							}), (0, vue_exports.createVNode)("path", {
								class: "opacity-75",
								fill: "currentColor",
								d: "M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
							})])), (0, vue_exports.createTextVNode)(" Verificando y enviando... ")])) : ((0, vue_exports.openBlock)(), (0, vue_exports.createBlock)("span", { key: 1 }, "Enviar código y enlace"))]),
							_: 1
						}, 8, ["class", "disabled"]), (0, vue_exports.createVNode)("button", {
							type: "button",
							onClick: handleBackToLogin,
							class: "w-full py-2 text-sm font-semibold text-gray-600 hover:text-gray-900 transition-colors text-center block"
						}, " ← Volver a iniciar sesión ")])], 32)
					])];
				}),
				_: 1
			}, _parent));
		};
	}
};
var _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
	const ssrContext = (0, vue_exports.useSSRContext)();
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Components/ForgotPasswordModal.vue");
	return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
//#endregion
//#region resources/js/Components/LoginModal.vue
var _sfc_main = {
	__name: "LoginModal",
	__ssrInlineRender: true,
	props: {
		show: {
			type: Boolean,
			default: false
		},
		maxWidth: {
			type: String,
			default: "md"
		},
		closeable: {
			type: Boolean,
			default: true
		}
	},
	emits: ["close"],
	setup(__props, { emit: __emit }) {
		const emit = __emit;
		const showForgotPasswordModal = (0, vue_exports.ref)(false);
		const form = useForm({
			email: "",
			password: "",
			remember: false
		});
		const submit = () => {
			form.post(route("login"), {
				onFinish: () => form.reset("password"),
				onSuccess: () => {
					close();
				}
			});
		};
		const close = () => {
			emit("close");
			form.reset();
			form.clearErrors();
		};
		const openForgotPassword = () => {
			const currentEmail = form.email;
			close();
			form.email = currentEmail;
			showForgotPasswordModal.value = true;
		};
		return (_ctx, _push, _parent, _attrs) => {
			_push(`<div${(0, server_renderer_exports.ssrRenderAttrs)(_attrs)}>`);
			_push((0, server_renderer_exports.ssrRenderComponent)(_sfc_main$2, {
				show: __props.show,
				"max-width": __props.maxWidth,
				closeable: __props.closeable,
				onClose: close
			}, {
				default: (0, vue_exports.withCtx)((_, _push, _parent, _scopeId) => {
					if (_push) {
						_push(`<div class="p-8"${_scopeId}><div class="flex items-center justify-between mb-8"${_scopeId}><div${_scopeId}><h2 class="text-2xl font-black text-gray-900 tracking-tight"${_scopeId}>Iniciar Sesión</h2><p class="text-sm text-gray-500 mt-1"${_scopeId}>Ingresa tus credenciales para continuar</p></div><button class="text-gray-400 hover:text-gray-600 transition-colors"${_scopeId}><svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"${_scopeId}><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"${_scopeId}></path></svg></button></div><form class="space-y-6"${_scopeId}><div${_scopeId}>`);
						_push((0, server_renderer_exports.ssrRenderComponent)(_sfc_main$3, {
							for: "modal_email",
							value: "Correo electrónico",
							class: "text-gray-700 font-semibold mb-1.5"
						}, null, _parent, _scopeId));
						_push((0, server_renderer_exports.ssrRenderComponent)(_sfc_main$4, {
							id: "modal_email",
							type: "email",
							class: "block w-full border-gray-200 focus:border-tech-orange focus:ring-tech-orange rounded-xl shadow-sm h-12",
							modelValue: (0, vue_exports.unref)(form).email,
							"onUpdate:modelValue": ($event) => (0, vue_exports.unref)(form).email = $event,
							required: "",
							autofocus: "",
							autocomplete: "username",
							placeholder: "ej: nombre@empresa.com"
						}, null, _parent, _scopeId));
						_push((0, server_renderer_exports.ssrRenderComponent)(_sfc_main$5, {
							class: "mt-2",
							message: (0, vue_exports.unref)(form).errors.email
						}, null, _parent, _scopeId));
						_push(`</div><div${_scopeId}><div class="flex items-center justify-between mb-1.5"${_scopeId}>`);
						_push((0, server_renderer_exports.ssrRenderComponent)(_sfc_main$3, {
							for: "modal_password",
							value: "Contraseña",
							class: "text-gray-700 font-semibold"
						}, null, _parent, _scopeId));
						_push(`<button type="button" class="text-xs font-semibold text-tech-orange hover:text-[#CC6200] transition-colors focus:outline-none"${_scopeId}> Recuperar contraseña </button></div>`);
						_push((0, server_renderer_exports.ssrRenderComponent)(_sfc_main$6, {
							id: "modal_password",
							class: "block w-full border-gray-200 focus:border-tech-orange focus:ring-tech-orange rounded-xl shadow-sm h-12",
							modelValue: (0, vue_exports.unref)(form).password,
							"onUpdate:modelValue": ($event) => (0, vue_exports.unref)(form).password = $event,
							required: "",
							autocomplete: "current-password",
							placeholder: "••••••••"
						}, null, _parent, _scopeId));
						_push((0, server_renderer_exports.ssrRenderComponent)(_sfc_main$5, {
							class: "mt-2",
							message: (0, vue_exports.unref)(form).errors.password
						}, null, _parent, _scopeId));
						_push(`</div><div class="flex items-center"${_scopeId}>`);
						_push((0, server_renderer_exports.ssrRenderComponent)(_sfc_main$7, {
							name: "remember",
							checked: (0, vue_exports.unref)(form).remember,
							"onUpdate:checked": ($event) => (0, vue_exports.unref)(form).remember = $event,
							id: "modal_remember"
						}, null, _parent, _scopeId));
						_push(`<label for="modal_remember" class="ms-2 text-sm text-gray-600 font-medium cursor-pointer selection:bg-transparent"${_scopeId}> Recordar sesión </label></div><div class="pt-2"${_scopeId}>`);
						_push((0, server_renderer_exports.ssrRenderComponent)(PrimaryButton_default, {
							class: ["w-full h-12 justify-center bg-tech-orange hover:bg-[#CC6200] text-white font-bold rounded-xl shadow-lg shadow-tech-orange/20 transition-all active:scale-[0.98] text-base", { "opacity-25 pointer-events-none": (0, vue_exports.unref)(form).processing }],
							disabled: (0, vue_exports.unref)(form).processing
						}, {
							default: (0, vue_exports.withCtx)((_, _push, _parent, _scopeId) => {
								if (_push) if ((0, vue_exports.unref)(form).processing) _push(`<span class="flex items-center gap-2"${_scopeId}><svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"${_scopeId}><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"${_scopeId}></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"${_scopeId}></path></svg> Iniciando... </span>`);
								else _push(`<span${_scopeId}>Entrar a mi cuenta</span>`);
								else return [(0, vue_exports.unref)(form).processing ? ((0, vue_exports.openBlock)(), (0, vue_exports.createBlock)("span", {
									key: 0,
									class: "flex items-center gap-2"
								}, [((0, vue_exports.openBlock)(), (0, vue_exports.createBlock)("svg", {
									class: "animate-spin h-5 w-5 text-white",
									xmlns: "http://www.w3.org/2000/svg",
									fill: "none",
									viewBox: "0 0 24 24"
								}, [(0, vue_exports.createVNode)("circle", {
									class: "opacity-25",
									cx: "12",
									cy: "12",
									r: "10",
									stroke: "currentColor",
									"stroke-width": "4"
								}), (0, vue_exports.createVNode)("path", {
									class: "opacity-75",
									fill: "currentColor",
									d: "M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
								})])), (0, vue_exports.createTextVNode)(" Iniciando... ")])) : ((0, vue_exports.openBlock)(), (0, vue_exports.createBlock)("span", { key: 1 }, "Entrar a mi cuenta"))];
							}),
							_: 1
						}, _parent, _scopeId));
						_push(`</div></form><div class="mt-8 pt-6 border-t border-gray-100 text-center"${_scopeId}><p class="text-sm text-gray-500"${_scopeId}> ¿No tienes una cuenta? <a href="#" class="font-bold text-gray-900 hover:text-tech-orange transition-colors"${_scopeId}>Empieza gratis por 14 días</a></p></div></div>`);
					} else return [(0, vue_exports.createVNode)("div", { class: "p-8" }, [
						(0, vue_exports.createVNode)("div", { class: "flex items-center justify-between mb-8" }, [(0, vue_exports.createVNode)("div", null, [(0, vue_exports.createVNode)("h2", { class: "text-2xl font-black text-gray-900 tracking-tight" }, "Iniciar Sesión"), (0, vue_exports.createVNode)("p", { class: "text-sm text-gray-500 mt-1" }, "Ingresa tus credenciales para continuar")]), (0, vue_exports.createVNode)("button", {
							onClick: close,
							class: "text-gray-400 hover:text-gray-600 transition-colors"
						}, [((0, vue_exports.openBlock)(), (0, vue_exports.createBlock)("svg", {
							class: "w-6 h-6",
							fill: "none",
							viewBox: "0 0 24 24",
							stroke: "currentColor"
						}, [(0, vue_exports.createVNode)("path", {
							"stroke-linecap": "round",
							"stroke-linejoin": "round",
							"stroke-width": "2",
							d: "M6 18L18 6M6 6l12 12"
						})]))])]),
						(0, vue_exports.createVNode)("form", {
							onSubmit: (0, vue_exports.withModifiers)(submit, ["prevent"]),
							class: "space-y-6"
						}, [
							(0, vue_exports.createVNode)("div", null, [
								(0, vue_exports.createVNode)(_sfc_main$3, {
									for: "modal_email",
									value: "Correo electrónico",
									class: "text-gray-700 font-semibold mb-1.5"
								}),
								(0, vue_exports.createVNode)(_sfc_main$4, {
									id: "modal_email",
									type: "email",
									class: "block w-full border-gray-200 focus:border-tech-orange focus:ring-tech-orange rounded-xl shadow-sm h-12",
									modelValue: (0, vue_exports.unref)(form).email,
									"onUpdate:modelValue": ($event) => (0, vue_exports.unref)(form).email = $event,
									required: "",
									autofocus: "",
									autocomplete: "username",
									placeholder: "ej: nombre@empresa.com"
								}, null, 8, ["modelValue", "onUpdate:modelValue"]),
								(0, vue_exports.createVNode)(_sfc_main$5, {
									class: "mt-2",
									message: (0, vue_exports.unref)(form).errors.email
								}, null, 8, ["message"])
							]),
							(0, vue_exports.createVNode)("div", null, [
								(0, vue_exports.createVNode)("div", { class: "flex items-center justify-between mb-1.5" }, [(0, vue_exports.createVNode)(_sfc_main$3, {
									for: "modal_password",
									value: "Contraseña",
									class: "text-gray-700 font-semibold"
								}), (0, vue_exports.createVNode)("button", {
									type: "button",
									onClick: openForgotPassword,
									class: "text-xs font-semibold text-tech-orange hover:text-[#CC6200] transition-colors focus:outline-none"
								}, " Recuperar contraseña ")]),
								(0, vue_exports.createVNode)(_sfc_main$6, {
									id: "modal_password",
									class: "block w-full border-gray-200 focus:border-tech-orange focus:ring-tech-orange rounded-xl shadow-sm h-12",
									modelValue: (0, vue_exports.unref)(form).password,
									"onUpdate:modelValue": ($event) => (0, vue_exports.unref)(form).password = $event,
									required: "",
									autocomplete: "current-password",
									placeholder: "••••••••"
								}, null, 8, ["modelValue", "onUpdate:modelValue"]),
								(0, vue_exports.createVNode)(_sfc_main$5, {
									class: "mt-2",
									message: (0, vue_exports.unref)(form).errors.password
								}, null, 8, ["message"])
							]),
							(0, vue_exports.createVNode)("div", { class: "flex items-center" }, [(0, vue_exports.createVNode)(_sfc_main$7, {
								name: "remember",
								checked: (0, vue_exports.unref)(form).remember,
								"onUpdate:checked": ($event) => (0, vue_exports.unref)(form).remember = $event,
								id: "modal_remember"
							}, null, 8, ["checked", "onUpdate:checked"]), (0, vue_exports.createVNode)("label", {
								for: "modal_remember",
								class: "ms-2 text-sm text-gray-600 font-medium cursor-pointer selection:bg-transparent"
							}, " Recordar sesión ")]),
							(0, vue_exports.createVNode)("div", { class: "pt-2" }, [(0, vue_exports.createVNode)(PrimaryButton_default, {
								class: ["w-full h-12 justify-center bg-tech-orange hover:bg-[#CC6200] text-white font-bold rounded-xl shadow-lg shadow-tech-orange/20 transition-all active:scale-[0.98] text-base", { "opacity-25 pointer-events-none": (0, vue_exports.unref)(form).processing }],
								disabled: (0, vue_exports.unref)(form).processing
							}, {
								default: (0, vue_exports.withCtx)(() => [(0, vue_exports.unref)(form).processing ? ((0, vue_exports.openBlock)(), (0, vue_exports.createBlock)("span", {
									key: 0,
									class: "flex items-center gap-2"
								}, [((0, vue_exports.openBlock)(), (0, vue_exports.createBlock)("svg", {
									class: "animate-spin h-5 w-5 text-white",
									xmlns: "http://www.w3.org/2000/svg",
									fill: "none",
									viewBox: "0 0 24 24"
								}, [(0, vue_exports.createVNode)("circle", {
									class: "opacity-25",
									cx: "12",
									cy: "12",
									r: "10",
									stroke: "currentColor",
									"stroke-width": "4"
								}), (0, vue_exports.createVNode)("path", {
									class: "opacity-75",
									fill: "currentColor",
									d: "M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
								})])), (0, vue_exports.createTextVNode)(" Iniciando... ")])) : ((0, vue_exports.openBlock)(), (0, vue_exports.createBlock)("span", { key: 1 }, "Entrar a mi cuenta"))]),
								_: 1
							}, 8, ["class", "disabled"])])
						], 32),
						(0, vue_exports.createVNode)("div", { class: "mt-8 pt-6 border-t border-gray-100 text-center" }, [(0, vue_exports.createVNode)("p", { class: "text-sm text-gray-500" }, [(0, vue_exports.createTextVNode)(" ¿No tienes una cuenta? "), (0, vue_exports.createVNode)("a", {
							href: "#",
							class: "font-bold text-gray-900 hover:text-tech-orange transition-colors"
						}, "Empieza gratis por 14 días")])])
					])];
				}),
				_: 1
			}, _parent));
			_push((0, server_renderer_exports.ssrRenderComponent)(_sfc_main$1, {
				show: showForgotPasswordModal.value,
				"initial-email": (0, vue_exports.unref)(form).email,
				onClose: ($event) => showForgotPasswordModal.value = false,
				onBackToLogin: ($event) => {
					showForgotPasswordModal.value = false;
					emit("open-login");
				}
			}, null, _parent));
			_push(`</div>`);
		};
	}
};
var _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
	const ssrContext = (0, vue_exports.useSSRContext)();
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Components/LoginModal.vue");
	return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
//#endregion
export { _sfc_main as default };
