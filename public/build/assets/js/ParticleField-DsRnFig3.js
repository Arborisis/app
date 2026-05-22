import{A as e,M as t,b as n,j as r,q as i}from"./inertia-BQVkfvId.js";import{A as a,O as o,T as s,c,j as l,l as u,o as d,u as f}from"./threejs-D-fUmfoy.js";var p={__name:`ParticleField`,setup(p){let m=i(null),h,g,_,v,y,b=0,x=[3462041,4876097,9414286,13935988];return e(()=>{if(window.matchMedia(`(prefers-reduced-motion: reduce)`).matches||!m.value)return;b=performance.now();let e=m.value.clientWidth,t=m.value.clientHeight;g=new a,_=new s(60,e/t,.1,100),_.position.z=5,h=new d({antialias:!0,alpha:!0}),h.setSize(e,t),h.setPixelRatio(Math.min(window.devicePixelRatio,2)),h.setClearColor(0,0),m.value.appendChild(h.domElement);let n=window.innerWidth<768?15:30,r=new Float32Array(n*3),i=new Float32Array(n*3),p=new Float32Array(n),S=new Float32Array(n),C=new f;for(let e=0;e<n;e++)r[e*3]=(Math.random()-.5)*12,r[e*3+1]=(Math.random()-.5)*8,r[e*3+2]=(Math.random()-.5)*6,C.setHex(x[Math.floor(Math.random()*x.length)]),i[e*3]=C.r,i[e*3+1]=C.g,i[e*3+2]=C.b,p[e]=.03+Math.random()*.06,S[e]=.2+Math.random()*.5;let w=new u;w.setAttribute(`position`,new c(r,3)),w.setAttribute(`color`,new c(i,3)),w.setAttribute(`size`,new c(p,1));let T=new l({uniforms:{uTime:{value:0}},vertexShader:`
            attribute float size;
            attribute vec3 color;
            varying vec3 vColor;
            uniform float uTime;
            void main() {
                vColor = color;
                vec3 pos = position;
                pos.y += sin(uTime * 0.3 + position.x) * 0.15;
                pos.x += cos(uTime * 0.2 + position.y) * 0.1;
                vec4 mvPosition = modelViewMatrix * vec4(pos, 1.0);
                gl_PointSize = size * (300.0 / -mvPosition.z);
                gl_Position = projectionMatrix * mvPosition;
            }
        `,fragmentShader:`
            varying vec3 vColor;
            void main() {
                float dist = length(gl_PointCoord - vec2(0.5));
                if (dist > 0.5) discard;
                float alpha = 1.0 - smoothstep(0.2, 0.5, dist);
                gl_FragColor = vec4(vColor, alpha * 0.6);
            }
        `,transparent:!0,depthWrite:!1,blending:2});v=new o(w,T),g.add(v);let E=()=>{if(!m.value)return;let e=m.value.clientWidth,t=m.value.clientHeight;_.aspect=e/t,_.updateProjectionMatrix(),h.setSize(e,t)};window.addEventListener(`resize`,E);let D=()=>{y=requestAnimationFrame(D);let e=(performance.now()-b)/1e3;T.uniforms.uTime.value=e,h.render(g,_)};D(),h.userData={onResize:E}}),r(()=>{y&&cancelAnimationFrame(y),h&&(h.userData?.onResize&&window.removeEventListener(`resize`,h.userData.onResize),v&&(v.geometry.dispose(),v.material.dispose()),h.dispose(),m.value&&h.domElement&&m.value.removeChild(h.domElement))}),(e,r)=>(t(),n(`div`,{ref_key:`canvasContainer`,ref:m,class:`absolute inset-0 w-full h-full pointer-events-none`,"aria-hidden":`true`},null,512))}};export{p as default};