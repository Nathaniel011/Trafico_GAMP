import test from 'node:test';
import assert from 'node:assert/strict';
import {captureBestPosition} from '../../resources/js/map-support.js';

function gpsHarness(t, secure = true) {
    let success, error, timeout;
    const cleared = [];
    const start = t.mock.fn((ok, fail) => {success=ok; error=fail; return 7;});
    Object.defineProperty(globalThis, 'window', {configurable:true,value:{isSecureContext:secure}});
    Object.defineProperty(globalThis, 'navigator', {configurable:true,value:{geolocation:{watchPosition:start,clearWatch:id=>cleared.push(id)}}});
    t.mock.method(Date,'now',()=>1000000);
    t.mock.method(globalThis,'setTimeout',fn=>{timeout=fn; return 17;});
    t.mock.method(globalThis,'clearTimeout',()=>{});
    const samples=[], errors=[];
    const stop=captureBestPosition({onPosition:position=>samples.push(position),onError:(failure,best)=>errors.push({failure,best})});
    return {start,samples,errors,stop,cleared,send:(accuracy,stamp=1000000,latitude=-19.589,longitude=-65.754)=>success({coords:{latitude,longitude,accuracy},timestamp:stamp}),fail:code=>error({code}),finish:()=>timeout()};
}
test('La captura ignora datos antiguos e inválidos y conserva las mejores lecturas recientes',t=>{
    const gps=gpsHarness(t);
    gps.send(40); gps.send(3,980000); gps.send(0); gps.send(2,1000000,91); gps.send(10); gps.send(20); gps.send(4);
    assert.deepEqual(gps.samples.map(sample=>sample.coords.accuracy),[40,10,4]);
    gps.finish();
    assert.deepEqual(gps.cleared,[7]);
    gps.send(1);
    assert.equal(gps.samples.length,3);
    assert.equal(gps.start.mock.calls[0].arguments[2].maximumAge,0);
    assert.equal(gps.start.mock.calls[0].arguments[2].enableHighAccuracy,true);
});
test('Confirmar o cambiar a un punto manual detiene la captura y descarta callbacks tardíos',t=>{
    const gps=gpsHarness(t);
    gps.send(8); gps.stop(); gps.send(2); gps.fail(3); gps.finish();
    assert.equal(gps.samples.length,1);
    assert.deepEqual(gps.cleared,[7]);
    assert.equal(gps.errors.length,0);
});
test('Un error de GPS conserva la lectura obtenida y cancela el seguimiento',t=>{
    const gps=gpsHarness(t);
    gps.send(12); gps.fail(2);
    assert.equal(gps.errors[0].best.coords.accuracy,12);
    assert.deepEqual(gps.cleared,[7]);
});
test('Sin HTTPS no se solicita posición al dispositivo',t=>{
    const gps=gpsHarness(t,false);
    assert.equal(gps.start.mock.callCount(),0);
    assert.equal(gps.errors[0].failure.code,0);
});
