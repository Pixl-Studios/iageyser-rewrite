#version 150

#moj_import <light.glsl>
#moj_import <fog.glsl>

in vec3 Position;
in vec4 Color;
in vec2 UV0;
in ivec2 UV1;
in ivec2 UV2;
in vec3 Normal;

uniform sampler2D Sampler0;
uniform sampler2D Sampler1;
uniform sampler2D Sampler2;

uniform mat4 ModelViewMat;
uniform mat4 ProjMat;
uniform mat4 TextureMat;
uniform int FogShape;

uniform vec3 Light0_Direction;
uniform vec3 Light1_Direction;

out float vertexDistance;
out vec4 vertexColor;
out vec4 lightMapColor;
out vec4 overlayColor;
out vec2 texCoord0;

uniform mat3 IViewRotMat;
out vec4 normal;
out vec2 origTexCoord0;
flat out int isTopLayer;
flat out int isHead;

#define VANILLA_SKIN_TEX_SIZE 64.
#define BONE_TEX_SIZE 16.
#define BONE_TEX_SIZE_NORM BONE_TEX_SIZE / VANILLA_SKIN_TEX_SIZE
#define PER_BONE_VTXS 24
#define PER_BONE_VTXS_BOTH_LAYERS 48

#define IS_GUI ProjMat[3][2] == -2.
#define MIGHT_BE_TOP_LAYER UV0.x > 0.5
#define b1 gl_VertexID % 12

#define handle_top_layer(index) if (a2 == index && MIGHT_BE_TOP_LAYER) isTopLayer = 1;

#define b2 1. - sign(length(texture(Sampler0, vec2(54. / 64., 20. / 64.)).rgb)) == 1.


#define NIGHT_COLOR normalize(vec3(42.0 / 255.0, 42.0 / 255.0, 72.0 / 255.0))
#define DAY_COLOR normalize(vec3(1.0, 1.0, 1.0))
float qq1(sampler2D lightMap) {
    vec3 sunLight = normalize(texture(lightMap, vec2(0.5 / 16.0, 15.5 / 16.0)).rgb);
    return clamp(pow(length(sunLight -  NIGHT_COLOR) / length(DAY_COLOR -  NIGHT_COLOR), 4.0), 0.0, 1.0);
}

#define SKIP_EMOTE { texCoord0=UV0; return; }

bool isBlankTransparent(sampler2D s, int x, int y) {
    vec4 v = texelFetch(s, ivec2(x, y), 0);
    return v.r == 0 && v.g == 0 && v.b == 0 && v.a == 0;
}

void main() {
    isTopLayer = 0;
    isHead = 0;
    origTexCoord0 = UV0;

    gl_Position = ProjMat * ModelViewMat * vec4(Position, 1.0);

    vertexDistance = fog_distance(ModelViewMat, IViewRotMat * Position, FogShape);
    vertexColor = minecraft_mix_light(Light0_Direction, Light1_Direction, Normal, Color);
    lightMapColor = texelFetch(Sampler2, UV2 / 16, 0);
    overlayColor = texelFetch(Sampler1, UV1, 0);
    texCoord0 = UV0;

    vec2 ss = textureSize(Sampler0, 0);
    bool xx2 = ss.x != VANILLA_SKIN_TEX_SIZE || ss.y != VANILLA_SKIN_TEX_SIZE;
    bool xx1 = UV0.y <= 0.25;
    if(!xx1 || IS_GUI || xx2) {
        texCoord0 = UV0;
        return;
    }

    if (texelFetch(Sampler0, ivec2(0, 0), 0).a != 1.0) SKIP_EMOTE
    if (texelFetch(Sampler0, ivec2(24, 0), 0).a != 1.0) SKIP_EMOTE
    if (!isBlankTransparent(Sampler0, 56, 0)) SKIP_EMOTE
    if (!isBlankTransparent(Sampler0, 19, 32)) SKIP_EMOTE

    vec2 aa0 = UV0;
    int a2 = (gl_VertexID / PER_BONE_VTXS) % 14;
    int a1 = gl_VertexID / PER_BONE_VTXS_BOTH_LAYERS;
    int a0 = a2 % 2;

    if(a1 == 6)
    {
        isHead = 1;
    }
    else if (a1 == 7)
    {
        handle_top_layer(1);
        aa0.x = UV0.x * 2 - a0;
        if (b2)
        {
            if (aa0.x < 0.375)
            {
                aa0.x *= 8. / 7.;
            }
            else if (aa0.x > 0.625 && aa0.x < 0.875)
            {
                int i = b1;
                if(i == 0 || i == 3)
                {
                    aa0.x = 10. / 14.;
                }
                else
                {
                    aa0.x = 11. / 14.;
                }
            }
            aa0.x *= 0.21875;
        }
        else
        {
            aa0.x *= BONE_TEX_SIZE_NORM;
        }

        aa0.y = UV0.y / 0.25;
        if (aa0.y < 0.75)
        {
            aa0.y *= 0.5;
        }
        aa0.x = aa0.x + (16. + a0 * BONE_TEX_SIZE) / VANILLA_SKIN_TEX_SIZE;
        aa0.y = aa0.y * BONE_TEX_SIZE_NORM + 0.75;

        aa0.x = aa0.x - (16. / 64.);

        if(a2 == 1 && UV0.x >= 0.5)
        isTopLayer = 1;
    }
    else if (a1 == 8)
    {
        handle_top_layer(2);
        aa0.y = UV0.y / (BONE_TEX_SIZE_NORM);
        if (aa0.y < 0.75)
        {
            aa0.y *= 0.5;
        }

        float i1 = UV0.x;
        if(a2 != 2)
        {
            i1 = i1 - 0.5;
        }

        i1 /= 0.5;

        if (i1 < 0.375)
        {
            i1 /= 1.5;
        }
        else if (i1 > 0.625 && i1 < 0.875)
        {
            i1 /= 1.125;
        }
        if (a2 == 2)
        {
            aa0.y = aa0.y * BONE_TEX_SIZE_NORM + BONE_TEX_SIZE_NORM;
        }
        else
        {
            aa0.y = aa0.y * BONE_TEX_SIZE_NORM + 0.5;
        }
        aa0.x = i1 * 0.375 + BONE_TEX_SIZE_NORM;
    }
    else if (a1 == 9)
    {
        handle_top_layer(6);
        aa0.x = UV0.x * 2 - a0;
        if (b2)
        {
            if (aa0.x < 0.375)
            {
                aa0.x *= 8. / 7.;
            }
            else if (aa0.x > 0.625 && aa0.x < 0.875)
            {
                int i = b1;
                if(i == 0 || i == 3)
                {
                    aa0.x = 10. / 14.;
                }
                else
                {
                    aa0.x = 11. / 14.;
                }
            }
            aa0.x *= 0.21875;
        }
        else
        {
            aa0.x *= BONE_TEX_SIZE_NORM;
        }

        aa0.y = UV0.y / 0.25;
        if (aa0.y < 0.75)
        {
            aa0.y *= 0.5;
        }
        aa0.x = aa0.x + (32. + a0 * BONE_TEX_SIZE) / VANILLA_SKIN_TEX_SIZE;
        aa0.y = aa0.y * BONE_TEX_SIZE_NORM + 0.75;

        if(a2 == 6 && UV0.x >= 0.5 && UV0.y == 0.25)
        isTopLayer = 1;
    }
    else if (a1 == 10)
    {
        handle_top_layer(8);
        aa0.x = UV0.x * 2 - a0;
        if (b2)
        {
            if (aa0.x < 0.375)
            {
                aa0.x *= 8. / 7.;
            }
            else if (aa0.x > 0.625 && aa0.x < 0.875)
            {
                int i = b1;
                if(i == 0 || i == 3)
                {
                    aa0.x = 10. / 14.;
                }
                else
                {
                    aa0.x = 11. / 14.;
                }
            }
            aa0.x *= 0.21875;
        }
        else
        {
            aa0.x *= (BONE_TEX_SIZE_NORM);
        }

        aa0.y = UV0.y / 0.25;
        if (aa0.y < 0.75)
        {
            aa0.y *= 0.5;
        }
        aa0.x = aa0.x + 0.625;
        aa0.y = aa0.y * (BONE_TEX_SIZE_NORM) + (a0 + 1) * 0.25;
    }
    else if (a1 == 11)
    {
        handle_top_layer(8);
        aa0.y = UV0.y / (BONE_TEX_SIZE_NORM);
        if (aa0.y < 0.75)
        {
            aa0.y *= 0.5;
        }

        if (a2 == 8)
        {
            aa0.x = UV0.x / 0.5 * BONE_TEX_SIZE_NORM;
            aa0.y = aa0.y * BONE_TEX_SIZE_NORM + BONE_TEX_SIZE_NORM;
        }
        else
        {
            aa0.x = (UV0.x - 0.5) / 0.5 * BONE_TEX_SIZE_NORM;
            aa0.y = aa0.y * BONE_TEX_SIZE_NORM + 0.5;
        }
    }
    else
    {
        texCoord0 = UV0;
        return;
    }
    texCoord0 = aa0;

    float dayFactor = qq1(Sampler2);
    if (dayFactor >= 0.2) {
        lightMapColor.rgb = mix(lightMapColor.rgb, vec3(1.0), 0.6);
    }
}
