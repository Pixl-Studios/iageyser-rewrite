#version 150

precision highp float;






#define hue(v)  ((.6+.6*cos(6.*(v)+vec4(0, 23, 21, 1)))+vec4(0., 0., 0., 1.) )

#define finalize() { \
    vertexDistance=length((ModelViewMat*vertex).xyz); \
    texCoord0=UV0; \
}

in vec3 Position;
in vec4 Color;
in vec2 UV0;
in ivec2 UV2;


uniform sampler2D Sampler0;

uniform sampler2D Sampler2;

uniform mat4 ModelViewMat;
uniform mat4 ProjMat;

uniform float GameTime;

out float vertexDistance;
out vec4 vertexColor;
out vec2 texCoord0;

float safeGameTime() {
    float gameTime=GameTime;
    
    if(gameTime <= 0) {
        gameTime=.5;
    }
    return gameTime;
}

float scaledTime() {
    return safeGameTime()*12000.;
}

void f_20f0e44a(inout vec4 vertex) {
    gl_Position=ProjMat*ModelViewMat*vertex;
}

void f_662a630a() {
    vertexColor=Color*texelFetch(Sampler2, UV2 / 16, 0);
}


void f_e6fa7e1e(inout vec4 vertex) {
    f_20f0e44a(vertex);
    if(Position.z==0. && gl_Position.x > .95) {
        vertexColor=vec4(0);
    }else{
        f_662a630a();
    }
    finalize();
}



void f_0bfef092() {
    vertexColor=hue(gl_Position.x+safeGameTime()*1000.)*texelFetch(Sampler2, UV2 / 16, 0);
}

void f_c41468d8() {
    gl_Position.y+=sin(scaledTime()+(gl_Position.x*6)) / 150.;
}

void f_35a76d7f(inout vec4 vertex) {
    f_20f0e44a(vertex);
    f_0bfef092();
    finalize();
}

void f_d5abf998(inout vec4 vertex) {
    f_20f0e44a(vertex);
    f_662a630a();
    f_c41468d8();
    finalize();
}

void f_477f887c(inout vec4 vertex) {
    f_20f0e44a(vertex);
    f_c41468d8();
    f_0bfef092();
    finalize();
}

void f_dc667a33(inout vec4 vertex) {
    f_662a630a();
    float vertexId=mod(gl_VertexID, 4.);
    if(vertex.z <= 0.) {
        if(vertexId==3. || vertexId==0.) {
            vertex.y+=cos(scaledTime() / 4)*.1;
            vertex.y+=max(cos(scaledTime() / 4)*.1, 0.);
        }
    }else{
        if(vertexId==3. || vertexId==0.) {
            vertex.y-=cos(scaledTime() / 4)*3;
            vertex.y-=max(cos(scaledTime() / 4)*4, 0.);
        }
    }
    f_20f0e44a(vertex);
    finalize();
}

void f_340b715a(inout vec4 vertex) {
    float vertexId=mod(gl_VertexID, 4.);
    if(vertex.z <= 0.) {
        if(vertexId==3. || vertexId==0.) {
            vertex.y+=cos(scaledTime() / 4)*.1;
            vertex.y+=max(cos(scaledTime() / 4)*.1, 0.);
        }
    }else{
        if(vertexId==3. || vertexId==0.) {
            vertex.y-=cos(scaledTime() / 4)*3;
            vertex.y-=max(cos(scaledTime() / 4)*4, 0.);
        }
    }
    f_0bfef092();
    f_20f0e44a(vertex);
    finalize();
}

void f_18887f44(inout vec4 vertex, float speed) {
    f_20f0e44a(vertex);
    float blink=abs(sin(scaledTime()*speed));
    vertexColor=Color*blink*texelFetch(Sampler2, UV2 / 16, 0);
    finalize();
}



void f_c88a220b(inout vec4 vertex) {
    f_20f0e44a(vertex);
    f_662a630a();
    vertexColor=vec4(1, 1, 1, vertexColor.a); 
    finalize();
}


void main() {
    vec4 vertex=vec4(Position, 1.);
    ivec3 iColor=ivec3(Color.xyz*255+vec3(.5));

    
    
    if(iColor==ivec3(255, 85, 85))
    {
        f_e6fa7e1e(vertex);
        return;
    }
    

    
    if(fract(Position.z) < .1) {
        
        
        if(iColor==ivec3(19, 23, 9))
        {
            gl_Position=vec4(2, 2, 2, 1);
            f_662a630a();
            finalize();
            return;
        }
        

        
        
        if(iColor==ivec3(57, 63, 63)) {
            
            
            f_20f0e44a(vertex);
            f_662a630a();
            finalize();
            return;
        }

        
        if(iColor==ivec3(57, 63, 62)) {
            f_d5abf998(vertex);
            return;
        }

        
        if(iColor==ivec3(57, 62, 63)) {
            
            f_d5abf998(vertex);
            return;
        }

        
        if(iColor==ivec3(57, 62, 62)) {
            f_dc667a33(vertex);
            return;
        }

        
        if(iColor==ivec3(57, 61, 63)) {
            f_dc667a33(vertex);
            return;
        }

        
        if(iColor==ivec3(57, 61, 62)) {
            f_18887f44(vertex, .5);
            return;
        }

        

        
    }

    
    
    if(iColor==ivec3(78, 92, 36))
    {
        f_c88a220b(vertex);
        return;
    }
    

    
    
    
    if(iColor==ivec3(230, 255, 254))
    {
        f_35a76d7f(vertex);
        return;
    }

    
    if(iColor==ivec3(230, 255, 250))
    {
        f_d5abf998(vertex);
        return;
    }

    
    if(iColor==ivec3(230, 251, 254))
    {
        f_477f887c(vertex);
        return;
    }

    
    if(iColor==ivec3(230, 251, 250))
    {
        f_dc667a33(vertex);
        return;
    }

    
    if(iColor==ivec3(230, 247, 254))
    {
        f_340b715a(vertex);
        return;
    }

    
    if(iColor==ivec3(230, 247, 250))
    {
        f_18887f44(vertex, .5);
        return;
    }

    
    

    
    
    
    if(iColor==ivec3(255, 255, 254))
    {
        f_35a76d7f(vertex);
        return;
    }

    
    if(iColor==ivec3(255, 255, 253))
    {
        f_d5abf998(vertex);
        return;
    }

    
    if(iColor==ivec3(255, 255, 25))
    {
        f_477f887c(vertex);
        return;
    }

    
    if(iColor==ivec3(255, 255, 251))
    {
        f_dc667a33(vertex);
        return;
    }

    
    if(iColor==ivec3(255, 254, 254))
    {
        f_340b715a(vertex);
        return;
    }
    

    
    f_20f0e44a(vertex);
    f_662a630a();
    finalize();
}