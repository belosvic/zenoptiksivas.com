/* ============================================================
   ZEN OPTİK — Prosedürel 3D gözlük sahnesi
   Scroll ile deterministik sökülme / birleşme
   ============================================================ */
import * as THREE from 'three';
import { RoomEnvironment } from 'three/addons/environments/RoomEnvironment.js';

const stage = document.getElementById('heroStage');
if (stage) init();

function prefersReduced() {
  return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
}

function webglOK() {
  try {
    const c = document.createElement('canvas');
    return !!(window.WebGLRenderingContext && (c.getContext('webgl2') || c.getContext('webgl')));
  } catch (e) { return false; }
}

function showFallback() {
  const img = stage.dataset.fallback;
  stage.insertAdjacentHTML('beforeend', `
    <div class="hero-fallback">
      ${img ? `<img src="${img}" alt="Zen Optik gözlük">` : ''}
    </div>`);
}

function init() {
  if (!webglOK() || prefersReduced()) { showFallback(); return; }

  const isMobile = matchMedia('(max-width: 760px)').matches;

  const easeInOut = t => t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2;
  const clamp01 = v => Math.min(Math.max(v, 0), 1);
  const tmpV = new THREE.Vector3();

  const curveSeg = isMobile ? 10 : 18;

  // ---------- Renderer ----------
  const renderer = new THREE.WebGLRenderer({ canvas: document.getElementById('glCanvas'), antialias: true, alpha: true, powerPreference: 'high-performance' });
  renderer.setPixelRatio(Math.min(window.devicePixelRatio, isMobile ? 1.5 : 1.75));
  renderer.setClearColor(0x000000, 0);
  renderer.toneMapping = THREE.ACESFilmicToneMapping;
  renderer.toneMappingExposure = 1.05;

  const scene = new THREE.Scene();
  scene.background = null;
  stage.style.background = 'radial-gradient(120% 90% at 50% 10%, #faf7f0 0%, #efe9de 55%, #e0d8c7 100%)';

  const camera = new THREE.PerspectiveCamera(30, 1, 0.1, 60);
  camera.position.set(0, 0.5, 9);

  // ---------- Ortam ışığı / reflections ----------
  const pmrem = new THREE.PMREMGenerator(renderer);
  const envRT = pmrem.fromScene(new RoomEnvironment(), 0.04);
  scene.environment = envRT.texture;
  pmrem.dispose();

  const key = new THREE.DirectionalLight(0xfff4e0, 2.4);
  key.position.set(4, 6, 5);
  scene.add(key);
  const rim = new THREE.DirectionalLight(0xcfa858, 1.1);
  rim.position.set(-6, 2, -4);
  scene.add(rim);
  const fill = new THREE.DirectionalLight(0xffffff, 0.5);
  fill.position.set(-3, -4, 6);
  scene.add(fill);
  // ---------- Malzemeler ----------
  const matAcetate = new THREE.MeshPhysicalMaterial({
    color: 0x1a150f, roughness: 0.22, metalness: 0.05,
    clearcoat: 1, clearcoatRoughness: 0.14, envMapIntensity: 1.15
  });
  const matGold = new THREE.MeshPhysicalMaterial({
    color: 0xb89348, roughness: 0.26, metalness: 1, envMapIntensity: 1.3
  });
  const matLens = new THREE.MeshPhysicalMaterial({
    color: 0x101c14, roughness: 0.05, metalness: 0.3,
    clearcoat: 1, clearcoatRoughness: 0.04, envMapIntensity: 2.6
  });
  const matPad = new THREE.MeshStandardMaterial({ color: 0xcfc6b4, roughness: 0.5 });

  // ---------- Geometri yardımcıları ----------
  function roundedRect(w, h, r) {
    const s = new THREE.Shape();
    const x = -w / 2, y = -h / 2;
    s.moveTo(x + r, y);
    s.lineTo(x + w - r, y);
    s.quadraticCurveTo(x + w, y, x + w, y + r);
    s.lineTo(x + w, y + h - r);
    s.quadraticCurveTo(x + w, y + h, x + w - r, y + h);
    s.lineTo(x + r, y + h);
    s.quadraticCurveTo(x, y + h, x, y + h - r);
    s.lineTo(x, y + r);
    s.quadraticCurveTo(x, y, x + r, y);
    return s;
  }
  function makeExtrude(shape, depth, bevel = 0.06) {
    return new THREE.ExtrudeGeometry(shape, {
      depth, bevelEnabled: bevel > 0, bevelThickness: bevel, bevelSize: bevel,
      bevelSegments: 2, curveSegments: curveSeg
    });
  }

  // ---------- Parça kurulumu ----------
  const glasses = new THREE.Group();
  scene.add(glasses);
  const RIM_X = 3.55;

  function part(name, meshes, x, y, z, explode, rot, window) {
    const g = new THREE.Group();
    meshes.forEach(m => g.add(m));
    g.position.set(x, y, z);
    glasses.add(g);
    return { group: g, explode: new THREE.Vector3(...explode), rot: new THREE.Vector3(...(rot || [0, 0, 0])), window, name, base: new THREE.Vector3(x, y, z), baseRot: g.rotation.clone() };
  }

  // Rim (çerçeve çerçevesi) — dış şekil + iç delik
  function buildRim(side) {
    const outer = roundedRect(6.0, 4.6, 1.75);
    const inner = roundedRect(4.95, 3.65, 1.35);
    outer.holes.push(inner);
    const geo = makeExtrude(outer, 0.42);
    geo.center();
    const mesh = new THREE.Mesh(geo, matAcetate);
    return part(side < 0 ? 'rimL' : 'rimR', [mesh], side * RIM_X, 0, 0,
      [side * 1.9, 0.55, -0.5], [0, 0, -side * 0.35], [0.34, 0.80]);
  }

  // Lens
  function buildLens(side) {
    const geo = makeExtrude(roundedRect(4.95, 3.65, 1.35), 0.16, 0.03);
    geo.center();
    const mesh = new THREE.Mesh(geo, matLens);
    return part(side < 0 ? 'lensL' : 'lensR', [mesh], side * RIM_X, 0, 0.12,
      [side * 1.55, -0.35, -2.3], [0, 0, side * 0.55], [0.16, 0.60]);
  }

  // Köprü
  function buildBridge() {
    const s = new THREE.Shape();
    s.moveTo(-0.75, 0); s.lineTo(0.75, 0);
    s.quadraticCurveTo(0.55, 0.5, 0.4, 0.62);
    s.lineTo(-0.4, 0.62);
    s.quadraticCurveTo(-0.55, 0.5, -0.75, 0);
    const geo = makeExtrude(s, 0.45, 0.05);
    geo.center();
    const mesh = new THREE.Mesh(geo, matAcetate);
    return part('bridge', [mesh], 0, 1.55, 0, [0, 1.35, 1.1], [0, 0, 0], [0.10, 0.50]);
  }

  // Burun pedi + kolu
  function buildPad(side) {
    const pad = new THREE.Mesh(new THREE.CapsuleGeometry(0.11, 0.22, 3, 8), matPad);
    pad.rotation.z = side * -0.5;
    const arm = new THREE.Mesh(new THREE.CylinderGeometry(0.04, 0.04, 0.6, 6), matGold);
    arm.rotation.z = Math.PI / 2 + side * -0.7;
    arm.position.set(side * 0.28, 0.28, 0);
    return part(side < 0 ? 'padL' : 'padR', [pad, arm], side * 0.85, -0.15, 0.5,
      [side * 0.55, -0.8, 1.4], [0, 0, side * -0.3], [0.02, 0.42]);
  }

  // Menteşe + vida
  function buildHinge(side) {
    const barrel = new THREE.Mesh(new THREE.BoxGeometry(0.3, 0.6, 0.44), matGold);
    const knuckle = new THREE.Mesh(new THREE.CylinderGeometry(0.13, 0.13, 0.44, 10), matGold);
    knuckle.rotation.z = Math.PI / 2;
    knuckle.position.set(-side * 0.28, 0, 0.28);
    const screw = new THREE.Mesh(new THREE.CylinderGeometry(0.07, 0.07, 0.5, 8), matGold);
    screw.rotation.z = Math.PI / 2;
    return part(side < 0 ? 'hingeL' : 'hingeR', [barrel, knuckle, screw], side * 6.55, 1.55, 0.1,
      [side * 2.4, 0.3, -1.3], [0, -side * 0.7, 0], [0.02, 0.42]);
  }

  // Sap (templo) — geriye uzanan kavisli kol
  function buildTemple(side) {
    const s = new THREE.Shape();
    s.moveTo(0, 0);
    s.lineTo(8.3, 0);
    s.quadraticCurveTo(10.0, -0.02, 10.25, -1.3);
    s.lineTo(9.85, -1.32);
    s.quadraticCurveTo(9.6, -0.36, 8.3, 0.42);
    s.lineTo(0, 0.42);
    s.lineTo(0, 0);
    const geo = makeExtrude(s, 0.32, 0.03);
    const mesh = new THREE.Mesh(geo, matAcetate);
    mesh.rotation.y = Math.PI / 2; // uzunluk → -z (arkaya)
    return part(side < 0 ? 'templeL' : 'templeR', [mesh], side * 6.55, 1.55, -0.35,
      [side * 2.6, 0.1, -3.5], [0, -side * 0.55, 0], [0.24, 0.72]);
  }

  const parts = [
    buildRim(-1), buildRim(1),
    buildLens(-1), buildLens(1),
    buildBridge(),
    buildPad(-1), buildPad(1),
    buildHinge(-1), buildHinge(1),
    buildTemple(-1), buildTemple(1)
  ];
  glasses.scale.setScalar(0.55);
  glasses.position.set(-0.35, -0.3, 0);
  glasses.rotation.x = 0.09;

  // ---------- Etiketler ----------
  const labelDefs = [
    { part: 'lensR',   title: 'Kristal Lens',    desc: 'Degrade kaplama, UV korumalı cam yüzey.', window: [0.26, 0.58], align: 'left' },
    { part: 'hingeL',  title: 'Menteşe',         desc: 'Kaplama metal gövde, mikrometrik uyum.',  window: [0.40, 0.72], align: 'right' },
    { part: 'bridge',  title: 'Burun Köprüsü',   desc: 'Dengeyi kuran merkez mimari.',           window: [0.30, 0.62], align: 'left' },
    { part: 'templeL', title: 'Asetat Sap',      desc: 'El işçiliğiyle şekillendirilmiş kol.',    window: [0.52, 0.86], align: 'right' },
  ];
  const labelEls = labelDefs.map(def => {
    const el = document.createElement('div');
    el.className = 'part-label' + (def.align === 'right' ? ' align-right' : '');
    el.innerHTML = `<div class="pl-card"><div class="pl-title">${def.title}</div><div class="pl-desc">${def.desc}</div></div><div class="pl-dot"></div>`;
    stage.appendChild(el);
    return { def, el };
  });

  // ---------- Scroll ilerlemesi ----------
  const zone = document.querySelector('.hero-scroll-zone');
  const progressEl = document.querySelector('.hero-progress span');
  let target = 0, current = 0;

  function readScroll() {
    // Test hook: ?p=0..1 zorlanmış ilerleme
    const forced = parseFloat(new URLSearchParams(location.search).get('p'));
    if (!isNaN(forced)) { target = clamp01(forced); return; }
    const rect = zone.getBoundingClientRect();
    const total = rect.height - window.innerHeight;
    const y = Math.min(Math.max(-rect.top, 0), Math.max(total, 1));
    target = total > 0 ? y / total : 0;
  }
  readScroll();
  window.addEventListener('scroll', readScroll, { passive: true });
  window.addEventListener('resize', () => { resize(); readScroll(); });

  // ---------- Boyutlandırma ----------
  let baseZ = 9.5;
  function resize() {
    const w = stage.clientWidth, h = stage.clientHeight;
    renderer.setSize(w, h, false);
    camera.aspect = w / h;
    // Sahneyi kadraja sığdır (sökülmüş hal ~10.5 dünya birimi + parallax payı)
    const vFov = camera.fov * Math.PI / 180;
    baseZ = THREE.MathUtils.clamp(12.6 / (2 * Math.tan(vFov / 2) * camera.aspect), 10, 48);
    camera.updateProjectionMatrix();
  }
  resize();

  // ---------- Görünürlük / render duraklatma ----------
  let visible = true;
  new IntersectionObserver(([e]) => { visible = e.isIntersecting; }, { threshold: 0.02 })
    .observe(zone);
  document.addEventListener('visibilitychange', () => { visible = !document.hidden && visible; });

  // ---------- Fare parallax ----------
  let mx = 0, my = 0;
  if (!isMobile) {
    window.addEventListener('pointermove', (e) => {
      mx = (e.clientX / window.innerWidth - 0.5) * 2;
      my = (e.clientY / window.innerHeight - 0.5) * 2;
    }, { passive: true });
  }

  function applyProgress(p) {
    for (const part of parts) {
      const [a, b] = part.window;
      const t = easeInOut(clamp01((p - a) / (b - a)));
      part.group.position.copy(part.base).addScaledVector(part.explode, t);
      part.group.rotation.set(
        part.baseRot.x + part.rot.x * t,
        part.baseRot.y + part.rot.y * t,
        part.baseRot.z + part.rot.z * t
      );
    }

    // Kamera koreografisi
    const camT = easeInOut(clamp01(p / 0.85));
    const zoom = camT < 0.3 ? 1 - 0.08 * (camT / 0.3) : 0.92 + 0.13 * ((camT - 0.3) / 0.7);
    camera.position.set(0.5 * camT + mx * 0.2, -0.1 - 0.2 * camT + my * 0.1, baseZ * zoom);
    camera.lookAt(0, -0.25 + camT * 0.05, 0);

    // Ürün grubunun hafif dönüşü
    glasses.rotation.y = 0.55 + Math.sin(performance.now() * 0.00035) * 0.05 + mx * 0.05 - camT * 0.1;

    // Etiketler
    const labelsOn = p > 0.18 && p < 0.94;
    for (const { def, el } of labelEls) {
      const [a, b] = def.window;
      const fade = clamp01((p - a) / 0.06) * clamp01((b - p) / 0.06);
      if (fade <= 0 || !labelsOn) { el.style.opacity = 0; continue; }
      const src = parts.find(pt => pt.name === def.part);
      src.group.getWorldPosition(tmpV);
      tmpV.project(camera);
      el.style.opacity = fade;
      el.style.left = ((tmpV.x * 0.5 + 0.5) * 100) + '%';
      el.style.top = ((-tmpV.y * 0.5 + 0.5) * 100) + '%';
      if (def.align === 'right') el.style.left = `calc(${((tmpV.x * 0.5 + 0.5) * 100)}% - 235px)`;
    }

    if (progressEl) progressEl.style.height = (p * 100) + '%';
  }

  // ---------- Render döngüsü ----------
  let last = performance.now();
  function frame(now) {
    requestAnimationFrame(frame);
    if (!visible) return;
    // Scroll hedefine yumuşak yaklaş (geri dönüşte de aynı yoldan)
    current += (target - current) * Math.min(1, (now - last) / 16.6 * 0.09);
    last = now;
    applyProgress(current);
    renderer.render(scene, camera);
  }
  requestAnimationFrame(frame);

  applyProgress(0);
  renderer.render(scene, camera);
}
