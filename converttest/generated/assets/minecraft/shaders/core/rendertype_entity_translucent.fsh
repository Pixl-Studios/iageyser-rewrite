#version 150
#moj_import <fog.glsl>
#moj_import <light.glsl>

uniform sampler2D Sampler0;

uniform vec4 ColorModulator;
uniform float FogStart;
uniform float FogEnd;
uniform vec4 FogColor;

in float vertexDistance;
in vec4 vertexColor;
in vec4 lightMapColor;
in vec4 overlayColor;
in vec2 texCoord0;

out vec4 fragColor;


uniform float GameTime;
uniform vec3 Light0_Direction;
uniform vec3 Light1_Direction;

flat in vec4 tint;
flat in vec3 vNormal;
flat in vec4 texel;

// Emote shit
in vec4 normal;
in vec2 origTexCoord0;
flat in int isTopLayer;
flat in int isHead;

void main() {
    // Original Vanilla shader rendering.
    vec4 color = texture(Sampler0, texCoord0);
    if (color.a < 0.1) {
        discard;
    }
    color *= vertexColor * ColorModulator;
    color.rgb = mix(overlayColor.rgb, color.rgb, overlayColor.a);
    color *= lightMapColor;
    fragColor = linear_fog(color, vertexDistance, FogStart, FogEnd, FogColor);

    // `== false` is a possible fix for `gl_FrontFacing` not working on MacOS https://github.com/PluginBugs/Issues-ItemsAdder/issues/2182
    // https://web.archive.org/web/20230504195931/https://makc3d.wordpress.com/2015/09/17/alternative-to-gl_frontfacing/
    // https://web.archive.org/web/20230504195715/https://github.com/Kitware/VTK/commit/221ba52da6313c7944b35b79a7f6077fbe41be7f
    if(isHead == 1 && gl_FrontFacing == false)
    discard;
    vec2 UV = texCoord0;
    if (isTopLayer == 1 || origTexCoord0.y >= 0.25)
    UV = origTexCoord0;
}