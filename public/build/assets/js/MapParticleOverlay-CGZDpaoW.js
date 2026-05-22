import{A as e,M as t,b as n,j as r,q as i}from"./inertia-BQVkfvId.js";import{A as a,O as o,P as s,T as c,b as l,c as u,j as d,l as f,o as p,u as m,v as h,y as g}from"./threejs-D-fUmfoy.js";var _=120,v=3,y=3,b={__name:`MapParticleOverlay`,setup(b){let x=i(null),S,C,w,T,E,D,O=0,k=0,A=0,j=0,M=0,N=new Float32Array(_*3),P=new Float32Array(_*3),F=new Float32Array(_*3),I=[new m(3462041),new m(15986919),new m(9414286)];function L(e){let t=l.degToRad(e.fov),n=2*Math.tan(t/2)*e.position.z;return{width:n*e.aspect,height:n}}return e(()=>{if(window.matchMedia(`(prefers-reduced-motion: reduce)`).matches||!x.value)return;M=performance.now();let e=x.value.clientWidth,t=x.value.clientHeight;C=new a,w=new c(50,e/t,.1,50),w.position.z=8,S=new p({antialias:!0,alpha:!0,premultipliedAlpha:!0}),S.setSize(e,t),S.setPixelRatio(Math.min(window.devicePixelRatio,2)),S.setClearColor(0,0),x.value.appendChild(S.domElement);let n=L(w),r=new Float32Array(_*3),i=new Float32Array(_);for(let e=0;e<_;e++){let t=e*3;N[t]=(Math.random()-.5)*n.width*1.3,N[t+1]=(Math.random()-.5)*n.height*1.3,N[t+2]=(Math.random()-.5)*3.5,P[t]=N[t],P[t+1]=N[t+1],P[t+2]=N[t+2],F[t]=0,F[t+1]=0,F[t+2]=0;let a=I[Math.floor(Math.random()*I.length)];r[t]=a.r,r[t+1]=a.g,r[t+2]=a.b,i[e]=.08+Math.random()*.14}let l=new f;l.setAttribute(`position`,new u(N,3)),l.setAttribute(`color`,new u(r,3)),l.setAttribute(`size`,new u(i,1)),T=new o(l,new d({uniforms:{uTime:{value:0}},vertexShader:`
            attribute float size;
            attribute vec3 color;
            varying vec3 vColor;
            varying float vAlpha;
            uniform float uTime;

            void main() {
                vColor = color;
                vec4 mvPosition = modelViewMatrix * vec4(position, 1.0);
                float pulse = 1.0 + 0.3 * sin(uTime * 1.8 + position.x * 4.0 + position.y * 3.0);
                gl_PointSize = size * pulse * (750.0 / max(2.5, -mvPosition.z));
                gl_Position = projectionMatrix * mvPosition;
                vAlpha = smoothstep(1.5, 5.0, -mvPosition.z) * 0.6 + 0.4;
            }
        `,fragmentShader:`
            varying vec3 vColor;
            varying float vAlpha;

            void main() {
                float dist = length(gl_PointCoord - vec2(0.5));
                if (dist > 0.5) discard;
                float alpha = (1.0 - smoothstep(0.12, 0.5, dist)) * vAlpha;
                float glow = 1.0 - smoothstep(0.0, 0.45, dist);
                vec3 finalColor = vColor * (0.9 + glow * 1.0);
                gl_FragColor = vec4(finalColor, alpha * 1.2);
            }
        `,transparent:!0,depthWrite:!1,blending:2})),C.add(T);let m=new f,b=_*y,R=new Float32Array(b*2*3),z=new Float32Array(b*2*3);m.setAttribute(`position`,new u(R,3)),m.setAttribute(`color`,new u(z,3)),E=new g(m,new h({vertexColors:!0,transparent:!0,opacity:.22,blending:2,depthWrite:!1})),C.add(E);let B=e=>{A=e.clientX/window.innerWidth*2-1,j=-(e.clientY/window.innerHeight)*2+1};window.addEventListener(`mousemove`,B);let V=()=>{if(!x.value)return;let e=x.value.clientWidth,t=x.value.clientHeight;w.aspect=e/t,w.updateProjectionMatrix(),S.setSize(e,t)};window.addEventListener(`resize`,V);let H=0,U=new s,W=new s,G=new s,K=()=>{D=requestAnimationFrame(K);let e=(performance.now()-M)/1e3;O+=(A-O)*.04,k+=(j-k)*.04,W.set(O,k,.5),W.unproject(w),G.copy(W).sub(w.position).normalize();let t=-w.position.z/G.z;U.copy(w.position).add(G.multiplyScalar(t));let n=T.geometry.attributes.position.array;for(let t=0;t<_;t++){let r=t*3,i=t*3+1,a=t*3+2,o=Math.sin(e*.25+P[r]*.6)*.001+Math.cos(e*.18+P[i]*.4)*5e-4,s=Math.cos(e*.22+P[r]*.5)*8e-4+Math.sin(e*.15+P[a]*.3)*3e-4,c=n[r]-U.x,l=n[i]-U.y,u=Math.sqrt(c*c+l*l);if(u<3&&u>.01){let e=(1-u/3)*.004;F[r]+=c/u*e,F[i]+=l/u*e}F[r]*=.965,F[i]*=.965,F[a]*=.965,n[r]+=F[r]+o,n[i]+=F[i]+s,n[a]+=F[a],n[r]+=(P[r]-n[r])*.003,n[i]+=(P[i]-n[i])*.003,n[a]+=(P[a]-n[a])*.003}if(T.geometry.attributes.position.needsUpdate=!0,H++,H%2==0){let e=E.geometry.attributes.position.array,t=E.geometry.attributes.color.array,r=0;for(let i=0;i<_;i++){let a=0,o=i*3,s=i*3+1,c=i*3+2;for(let l=i+1;l<_&&a<y;l++){let i=l*3,u=l*3+1,d=l*3+2,f=n[o]-n[i],p=n[s]-n[u],m=n[c]-n[d],h=Math.sqrt(f*f+p*p+m*m);if(h<v){let l=1-h/v,f=r*6;e[f]=n[o],e[f+1]=n[s],e[f+2]=n[c],e[f+3]=n[i],e[f+4]=n[u],e[f+5]=n[d];let p=.15*l,m=.75*l,g=.55*l;t[f]=p,t[f+1]=m,t[f+2]=g,t[f+3]=p,t[f+4]=m,t[f+5]=g,a++,r++}}}E.geometry.setDrawRange(0,r*2),E.geometry.attributes.position.needsUpdate=!0,E.geometry.attributes.color.needsUpdate=!0}T.material.uniforms.uTime.value=e,S.render(C,w)};K(),S.userData={onMouseMove:B,onResize:V}}),r(()=>{if(D&&cancelAnimationFrame(D),S){let{onMouseMove:e,onResize:t}=S.userData||{};e&&window.removeEventListener(`mousemove`,e),t&&window.removeEventListener(`resize`,t),T&&(T.geometry.dispose(),T.material.dispose()),E&&(E.geometry.dispose(),E.material.dispose()),S.dispose(),x.value&&S.domElement&&x.value.removeChild(S.domElement)}}),(e,r)=>(t(),n(`div`,{ref_key:`canvasContainer`,ref:x,class:`absolute inset-0 w-full h-full pointer-events-none z-[700]`,"aria-hidden":`true`},null,512))}};export{b as default};