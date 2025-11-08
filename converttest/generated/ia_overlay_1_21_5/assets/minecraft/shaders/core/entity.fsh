#version 150
#moj_import <minecraft:fog.glsl>
#moj_import <minecraft:light.glsl>



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

uniform vec3 Light0_Direction;
uniform vec3 Light1_Direction;

flat in vec4 tint;
flat in vec3 vNormal;

// Emote stuff
in vec2 origTexCoord0;
flat in int isTopLayer;
flat in int isHead;

flat in vec2 textureDimensions;
flat in int isLeatherLayer;
in float vGameTime;

void main() {
    vec4 color;
    

    if (isHead == 1 && gl_FrontFacing == false) discard;

    vec2 UV = texCoord0;
    if (isTopLayer == 1 || origTexCoord0.y >= 0.25)
    UV = origTexCoord0;

    color = texture(Sampler0, UV);
#ifdef ALPHA_CUTOUT
    if (color.a < ALPHA_CUTOUT) discard;
#endif
    color *= vertexColor * ColorModulator;
#ifndef NO_OVERLAY
    color.rgb = mix(overlayColor.rgb, color.rgb, overlayColor.a);
#endif
#ifndef EMISSIVE
    color *= lightMapColor;
#endif
    fragColor = linear_fog(color, vertexDistance, FogStart, FogEnd, FogColor);
}